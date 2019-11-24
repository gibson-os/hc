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
     * @var SenderService
     */
    private $sender;

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
     */
    public function __construct(
        SenderService $sender,
        EventService $event,
        TransformService $transform,
        ModuleRepository $moduleRepository,
        Type $typeRepository
    ) {
        $this->sender = $sender;
        $this->event = $event;
        $this->transform = $transform;
        $this->moduleRepository = $moduleRepository;
        $this->typeRepository = $typeRepository;
    }

    /**
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
            ->setDirection(Log::DIRECTION_INPUT);

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
     * @throws AbstractException
     */
    public function send(Master $master, int $type, string $data): void
    {
        $this->sender->send($master, $type, $data);
    }

    /**
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
                ->setDirection(Log::DIRECTION_OUTPUT)
                ->save();
        } catch (AbstractException $exception) {
            throw $exception;
        }

        $master->setAddress($address);
        $master->save();
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
        $data = $this->sender->receiveReadData($master, $type);

        if ($address !== $this->transform->asciiToInt($data, 0)) {
            new ReceiveError('Slave Adresse stimmt nicht überein!');
        }

        if ($command !== $this->transform->asciiToInt($data, 1)) {
            new ReceiveError('Kommando stimmt nicht überein!');
        }

        return substr($data, 2);
    }

    /**
     * @throws FileNotFound
     */
    public function receiveReceiveReturn(Master $master): void
    {
        $this->sender->receiveReceiveReturn($master);
    }
}
