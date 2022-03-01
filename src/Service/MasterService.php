<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use DateTime;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\DateTimeService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Dto\Direction;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Mapper\MasterMapper;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;
use GibsonOS\Module\Hc\Service\Protocol\UdpService;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;
use JsonException;
use Psr\Log\LoggerInterface;
use ReflectionException;

class MasterService
{
    public const TYPE_RECEIVE_RETURN = 0;

    public const TYPE_HANDSHAKE = 1;

    public const TYPE_STATUS = 2;

    private const TYPE_NEW_SLAVE = 3;

    public const TYPE_SLAVE_HAS_INPUT_CHECK = 4;

    private const TYPE_SCAN_BUS = 5;

    public const TYPE_DATA = 255;

    public function __construct(
        private SenderService $senderService,
        private SlaveFactory $slaveFactory,
        private MasterMapper $masterMapper,
        private LogRepository $logRepository,
        private ModuleRepository $moduleRepository,
        private TypeRepository $typeRepository,
        private LoggerInterface $logger,
        private MasterRepository $masterRepository,
        private DateTimeService $dateTimeService,
        private ModelManager $modelManager
    ) {
    }

    /**
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    public function receive(Master $master, BusMessage $busMessage): void
    {
        $data = $busMessage->getData();
        $log = $this->logRepository->create(
            $busMessage->getType(),
            $data ?? '',
            Direction::INPUT
        )
            ->setMaster($master)
        ;

        $this->logger->info(sprintf('Receive type %d', $busMessage->getType()));

        if ($busMessage->getType() === self::TYPE_NEW_SLAVE) {
            $slaveAddress = $busMessage->getSlaveAddress();

            if ($slaveAddress === null) {
                throw new ReceiveError('Slave Address is null!');
            }

            $this->logger->info(sprintf('New Slave %d', $slaveAddress));
            $slave = $this->slaveHandshake($master, $slaveAddress);
        } else {
            $command = $busMessage->getCommand();

            if ($command === null) {
                throw new ReceiveError('Command is null!');
            }

            $this->logger->info(sprintf(
                'Receive command %d for slave address %s',
                $command,
                $busMessage->getSlaveAddress() ?? 0
            ));
            $slave = $this->slaveReceive($master, $busMessage);
            $log->setCommand($command);
        }

        $this->modelManager->save(
            $slave
                ->setOffline(false)
                ->setModified(new DateTime())
        );
        $this->modelManager->save($log->setModule($slave));
    }

    /**
     * @throws AbstractException
     */
    public function send(Master $master, BusMessage $busMessage): void
    {
        $this->logger->debug(sprintf(
            'Send data "%s" to %s',
            $busMessage->getData() ?? '',
            $busMessage->getMasterAddress()
        ));
        $this->senderService->send($busMessage, $master->getProtocol());
    }

    /**
     * @throws AbstractException
     * @throws GetError
     * @throws SaveError
     * @throws JsonException
     * @throws ReflectionException
     */
    public function handshake(ProtocolInterface $protocolService, BusMessage $busMessage): void
    {
        $protocolName = $protocolService->getName();
        $data = $busMessage->getData();

        if (empty($data)) {
            throw new GetError('No master name transmitted!');
        }

        try {
            $master = $this->masterRepository->getByName($data, $protocolName);
            $this->modelManager->save(
                $master
                    ->setAddress($busMessage->getMasterAddress())
                    ->setModified($this->dateTimeService->get())
            );
        } catch (SelectError) {
            $master = $this->masterRepository->add($data, $protocolName, $busMessage->getMasterAddress());
        }

        $this->send(
            $master,
            (new BusMessage($master->getAddress(), MasterService::TYPE_HANDSHAKE))
                ->setData(
                    chr($master->getSendPort() >> 8) .
                    chr($master->getSendPort() & 255)
                )
                ->setPort(UdpService::START_PORT)
        );
    }

    /**
     * @throws AbstractException
     */
    public function scanBus(Master $master): void
    {
        $busMessage = (new BusMessage($master->getAddress(), self::TYPE_SCAN_BUS))
            ->setPort($master->getSendPort())
        ;
        $this->send($master, $busMessage);
        $this->receiveReceiveReturn($master, $busMessage);
    }

    /**
     * @throws FactoryError
     * @throws GetError
     * @throws ReceiveError
     */
    public function receiveReadData(Master $master, BusMessage $busMessage): BusMessage
    {
        $receivedBusMessage = $this->senderService->receiveReadData($master, $busMessage->getType());
        $this->masterMapper->extractSlaveDataFromMessage($receivedBusMessage);

        if ($busMessage->getSlaveAddress() !== $receivedBusMessage->getSlaveAddress()) {
            throw new ReceiveError(sprintf(
                'Slave address %d not equal with received %d!',
                $busMessage->getSlaveAddress() ?? 0,
                $receivedBusMessage->getSlaveAddress() ?? 0
            ));
        }

        if ($busMessage->getCommand() !== $receivedBusMessage->getCommand()) {
            throw new ReceiveError('Command not equal!');
        }

        return $receivedBusMessage;
    }

    /**
     * @throws FactoryError
     */
    public function receiveReceiveReturn(Master $master, BusMessage $busMessage): void
    {
        $this->senderService->receiveReceiveReturn($master, $busMessage);
    }

    /**
     * @throws FactoryError
     * @throws SelectError
     */
    private function slaveHandshake(Master $master, int $address): Module
    {
        try {
            $slave = $this->moduleRepository->getByAddress($address, (int) $master->getId());
        } catch (SelectError) {
            $this->logger->debug(sprintf(
                'Add new slave with address %d on master address %s',
                $address,
                $master->getAddress()
            ));
            $slave = (new Module())
                ->setName('Neues Modul')
                ->setAddress($address)
                ->setMaster($master)
                ->setAdded(new DateTime())
            ;

            try {
                $slave->setType($this->typeRepository->getByDefaultAddress($address));
            } catch (SelectError) {
                $slave->setType($this->typeRepository->getByHelperName('blank'));
            }
        }

        $slaveService = $this->slaveFactory->get($slave->getType()->getHelper());

        return $slaveService->handshake($slave);
    }

    /**
     * @throws FactoryError
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
