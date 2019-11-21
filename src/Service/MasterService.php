<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use DateTime;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Module as ModuleRepository;
use GibsonOS\Module\Hc\Repository\Type;
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
     * @var ServerService
     */
    private $server;

    /**
     * @var EventService
     */
    private $event;

    /**
     * @var TransformService
     */
    private $transform;

    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    /**
     * @var Type
     */
    private $typeRepository;

    /**
     * Master constructor.
     *
     * @param ServerService    $server
     * @param EventService     $event
     * @param TransformService $transform
     * @param ModuleRepository $moduleRepository
     * @param Type             $typeRepository
     */
    public function __construct(
        ServerService $server,
        EventService $event,
        TransformService $transform,
        ModuleRepository $moduleRepository,
        Type $typeRepository
    ) {
        $this->server = $server;
        $this->event = $event;
        $this->transform = $transform;
        $this->moduleRepository = $moduleRepository;
        $this->typeRepository = $typeRepository;
    }

    /**
     * @param Master          $master
     * @param AbstractHcSlave $slave
     * @param int             $type
     * @param string          $data
     *
     * @throws AbstractException
     * @throws DateTimeError
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     * @throws GetError
     */
    public function receive(Master $master, AbstractHcSlave $slave, int $type, string $data): void
    {
        $log = (new Log())
            ->setMasterId($master->getId())
            ->setType($type)
            ->setData($this->transform->asciiToHex($data))
            ->setDirection(ServerService::DIRECTION_INPUT);

        $address = $this->transform->asciiToInt($data, 0);
        $command = $this->transform->asciiToInt($data, 1);
        $data = substr($data, 2);

        echo 'Type: ' . $type . PHP_EOL;
        echo 'Command: ' . $command . PHP_EOL;
        if ($type === MasterService::TYPE_NEW_SLAVE) {
            $log->save();

            echo 'New Slave ' . $address . PHP_EOL;

            try {
                $slaveModel = $this->moduleRepository->getByAddress($address, (int) $master->getId());
            } catch (SelectError $exception) {
                $slaveModel = (new Module())
                    ->setAddress($address)
                    ->setMaster($master)
                ;

                try {
                    $slaveModel->setType($this->typeRepository->getByDefaultAddress($address));
                } catch (SelectError $e) {
                    $slaveModel->setType($this->typeRepository->getById(255));
                }

                $slaveModel->setName('Neues Modul');
            }

            $slave->handshake($slaveModel);
        } else {
            $slaveModel = $this->moduleRepository->getByAddress($address, (int) $master->getId());
            $slave->receive($slaveModel, $type, $command, $data);

            $log
                ->setModuleId($slaveModel->getId())
                ->setCommand($command)
                ->setData($this->transform->asciiToHex($data))
                ->save()
            ;
        }

        $slaveModel
            ->setOffline(false)
            ->setModified(new DateTime())
            ->setMaster($master)
            ->save()
        ;
    }

    /**
     * @param Master $master
     * @param int    $type
     * @param string $data
     *
     * @throws AbstractException
     */
    public function send(Master $master, int $type, string $data): void
    {
        $this->server->send($master->getAddress(), $type, $data);
    }

    /**
     * @param Master $master
     * @param int    $address
     *
     * @throws AbstractException
     */
    public function setAddress(Master $master, int $address): void
    {
        try {
            $data = $master->getName() . chr($address);
            $this->send($master, MasterService::TYPE_HANDSHAKE, $data);
            $this->receiveReceiveReturn($master);

            (new Log())
                ->setMasterId($master->getId())
                ->setType(MasterService::TYPE_HANDSHAKE)
                ->setData($this->transform->asciiToHex($data))
                ->setDirection(ServerService::DIRECTION_OUTPUT)
                ->save();
        } catch (AbstractException $exception) {
            throw $exception;
        }

        $master->setAddress($address);
        $master->save();
    }

    /**
     * @param Master $master
     *
     * @throws AbstractException
     */
    public function scanBus(Master $master): void
    {
        $this->send($master, self::TYPE_SCAN_BUS, '');
        $this->receiveReceiveReturn($master);
    }

    /**
     * @param Master $master
     * @param int    $address
     * @param int    $type
     * @param int    $command
     *
     * @throws ReceiveError
     *
     * @return string
     */
    public function receiveReadData(Master $master, int $address, int $type, int $command): string
    {
        $data = $this->server->receiveReadData($master->getAddress(), $type);

        if ($address !== $this->transform->asciiToInt($data, 0)) {
            new ReceiveError('Slave Adresse stimmt nicht überein!');
        }

        if ($command !== $this->transform->asciiToInt($data, 1)) {
            new ReceiveError('Kommando stimmt nicht überein!');
        }

        return substr($data, 2);
    }

    /**
     * @param Master $master
     */
    public function receiveReceiveReturn(Master $master): void
    {
        $this->server->receiveReceiveReturn($master->getAddress());
    }
}
