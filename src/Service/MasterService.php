<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use DateTime;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Formatter\MasterFormatter;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;

class MasterService extends AbstractService
{
    const TYPE_RECEIVE_RETURN = 0;

    const TYPE_HANDSHAKE = 1;

    const TYPE_STATUS = 2;

    const TYPE_NEW_SLAVE = 3;

    const TYPE_SLAVE_IS_HC = 4;

    const TYPE_SCAN_BUS = 5;

    const TYPE_DATA = 255;

    /**
     * @var SenderService
     */
    private $senderService;

    /**
     * @var EventService
     */
    private $eventService;

    /**
     * @var TransformService
     */
    private $transformService;

    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    /**
     * @var TypeRepository
     */
    private $typeRepository;

    /**
     * @var SlaveFactory
     */
    private $slaveFactory;

    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @var MasterFormatter
     */
    private $masterFormatter;

    /**
     * Master constructor.
     */
    public function __construct(
        SenderService $senderService,
        EventService $eventService,
        TransformService $transformService,
        SlaveFactory $slaveFactory,
        MasterFormatter $masterFormatter,
        LogRepository $logRepository,
        ModuleRepository $moduleRepository,
        TypeRepository $typeRepository
    ) {
        $this->senderService = $senderService;
        $this->eventService = $eventService;
        $this->transformService = $transformService;
        $this->slaveFactory = $slaveFactory;
        $this->logRepository = $logRepository;
        $this->moduleRepository = $moduleRepository;
        $this->typeRepository = $typeRepository;
        $this->masterFormatter = $masterFormatter;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws FileNotFound
     * @throws GetError
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     */
    public function receive(Master $master, BusMessage $busMessage): void
    {
        $data = $busMessage->getData();
        $log = $this->logRepository->create(
            $busMessage->getType(),
            $data ?? '',
            Log::DIRECTION_INPUT
        )
            ->setMaster($master)
        ;

        echo 'Type: ' . $busMessage->getType() . PHP_EOL;

        if ($busMessage->getType() === MasterService::TYPE_NEW_SLAVE) {
            $slaveAddress = $busMessage->getSlaveAddress();

            if ($slaveAddress === null) {
                throw new ReceiveError('Slave Address is null!');
            }

            echo 'New Slave ' . $slaveAddress . PHP_EOL;
            $slave = $this->slaveHandshake($master, $slaveAddress);
        } else {
            $command = $busMessage->getCommand();

            if ($command === null) {
                throw new ReceiveError('Command is null!');
            }

            echo 'Command: ' . $command . PHP_EOL;
            $slave = $this->slaveReceive($master, $busMessage);
            $log->setCommand($command);
        }

        $slave
            ->setOffline(false)
            ->setModified(new DateTime())
            ->save()
        ;
        $log
            ->setModule($slave)
            ->save()
        ;
    }

    /**
     * @throws AbstractException
     */
    public function send(Master $master, BusMessage $busMessage): void
    {
        $this->senderService->send($busMessage, $master->getProtocol());
    }

    /**
     * @throws AbstractException
     */
    public function scanBus(Master $master): void
    {
        $this->send($master, new BusMessage($master->getAddress(), self::TYPE_SCAN_BUS, true));
        $this->receiveReceiveReturn($master);
    }

    /**
     * @throws FactoryError
     * @throws GetError
     * @throws ReceiveError
     */
    public function receiveReadData(Master $master, BusMessage $busMessage): BusMessage
    {
        $receivedBusMessage = $this->senderService->receiveReadData($master, $busMessage->getType());

        if ($busMessage->getSlaveAddress() !== $receivedBusMessage->getSlaveAddress()) {
            throw new ReceiveError('Slave address not equal!');
        }

        if ($busMessage->getCommand() !== $receivedBusMessage->getCommand()) {
            throw new ReceiveError('Command not equal!');
        }

        return $receivedBusMessage;
    }

    /**
     * @throws FileNotFound
     */
    public function receiveReceiveReturn(Master $master): void
    {
        $this->senderService->receiveReceiveReturn($master);
    }

    /**
     * @throws DateTimeError
     * @throws FileNotFound
     * @throws GetError
     * @throws SelectError
     */
    private function slaveHandshake(Master $master, int $address): Module
    {
        try {
            $slave = $this->moduleRepository->getByAddress($address, (int) $master->getId());
        } catch (SelectError $exception) {
            $slave = (new Module())
                ->setName('Neues Modul')
                ->setAddress($address)
                ->setMaster($master)
                ->setAdded(new DateTime())
            ;

            try {
                $slave->setType($this->typeRepository->getByDefaultAddress($address));
            } catch (SelectError $e) {
                $slave->setType($this->typeRepository->getByHelperName('blank'));
            }
        }

        $slaveService = $this->slaveFactory->get($slave->getType()->getHelper());

        return $slaveService->handshake($slave);
    }

    /**
     * @throws DateTimeError
     * @throws FileNotFound
     * @throws GetError
     * @throws ReceiveError
     * @throws SelectError
     */
    private function slaveReceive(Master $master, BusMessage $busMessage): Module
    {
        $slaveModel = $this->moduleRepository->getByAddress(
            $busMessage->getSlaveAddress() ?? 0,
            $master->getId() ?? 0
        );
        $slave = $this->slaveFactory->get($slaveModel->getType()->getHelper());

        if (!$slave instanceof AbstractHcSlave) {
            throw new ReceiveError(sprintf(
                '%s ist vom Typ %s und damit kein HC Sklave!',
                $slaveModel->getName(),
                $slaveModel->getType()->getName()
            ));
        }

        $slave->receive($slaveModel, $busMessage);

        return $slaveModel;
    }
}
