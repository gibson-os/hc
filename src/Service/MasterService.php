<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use DateTime;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
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
     * Master constructor.
     */
    public function __construct(
        SenderService $senderService,
        EventService $eventService,
        TransformService $transformService,
        SlaveFactory $slaveFactory,
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
    }

    /**
     * @throws DateTimeError
     * @throws FileNotFound
     * @throws GetError
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     */
    public function receive(Master $master, int $type, string $data): void
    {
        $log = $this->logRepository->create(
            $type,
            strlen($data) < 2 ? '' : $this->transformService->asciiToHex(substr($data, 2)),
            Log::DIRECTION_INPUT
        )
            ->setMaster($master)
        ;

        $address = $this->transformService->asciiToUnsignedInt($data, 0);

        echo 'Type: ' . $type . PHP_EOL;

        if ($type === MasterService::TYPE_NEW_SLAVE) {
            echo 'New Slave ' . $address . PHP_EOL;
            $slave = $this->slaveHandshake($master, $address);
        } else {
            $command = $this->transformService->asciiToUnsignedInt($data, 1);
            echo 'Command: ' . $command . PHP_EOL;
            $slave = $this->slaveReceive($master, $address, $type, $command, substr($data, 2));
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
    public function send(Master $master, int $type, string $data): void
    {
        $this->senderService->send($master, $type, $data);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws FileNotFound
     * @throws SaveError
     */
    public function setAddress(Master $master, int $address): void
    {
        $data = $master->getName() . chr($address);
        $this->send($master, MasterService::TYPE_HANDSHAKE, $data);
        $this->receiveReceiveReturn($master);

        $this->logRepository->create(
            MasterService::TYPE_HANDSHAKE,
            $this->transformService->asciiToHex($data),
            Log::DIRECTION_OUTPUT
        )
            ->setMaster($master)
            ->save()
        ;

        $master
            ->setAddress($address)
            ->save()
        ;
    }

    /**
     * @throws AbstractException
     */
    public function scanBus(Master $master): void
    {
        $this->send($master, self::TYPE_SCAN_BUS, '');
        $this->receiveReceiveReturn($master);
    }

    /**
     * @throws ReceiveError
     * @throws FileNotFound
     */
    public function receiveReadData(Master $master, int $address, int $type, int $command): string
    {
        $data = $this->senderService->receiveReadData($master, $type);

        if ($address !== $this->transformService->asciiToUnsignedInt($data, 0)) {
            throw new ReceiveError('Slave Adresse stimmt nicht überein!');
        }

        if ($command !== $this->transformService->asciiToUnsignedInt($data, 1)) {
            throw new ReceiveError('Kommando stimmt nicht überein!');
        }

        return substr($data, 2);
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
     * @throws SaveError
     * @throws SelectError
     */
    private function slaveHandshake(Master $master, int $address): Module
    {
        try {
            $slave = $this->moduleRepository->getByAddress($address, (int) $master->getId());
        } catch (SelectError $exception) {
            try {
                $slave = $this->moduleRepository->create(
                    'Neues Modul',
                    $this->typeRepository->getByDefaultAddress($address)
                )
                    ->setAddress($address)
                    ->setMaster($master)
                ;
                $slave->save();
            } catch (SelectError $exception) {
                // @todo Sklave mit unbekannter Adresse gefunden
                throw $exception;
            }
        }

        $slaveService = $this->slaveFactory->get($slave->getType()->getHelper());
        $slaveService->handshake($slave);

        return $slave;
    }

    /**
     * @throws DateTimeError
     * @throws FileNotFound
     * @throws GetError
     * @throws ReceiveError
     * @throws SelectError
     */
    private function slaveReceive(Master $master, int $address, int $type, int $command, string $data): Module
    {
        $slaveModel = $this->moduleRepository->getByAddress($address, (int) $master->getId());
        $slave = $this->slaveFactory->get($slaveModel->getType()->getHelper());

        if (!$slave instanceof AbstractHcSlave) {
            throw new ReceiveError(sprintf(
                '%s ist vom Typ %s und damit kein HC Sklave!',
                $slaveModel->getName(),
                $slaveModel->getType()->getName()
            ));
        }

        $slave->receive($slaveModel, $type, $command, $data);

        return $slaveModel;
    }
}
