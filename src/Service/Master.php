<?php
namespace GibsonOS\Module\Hc\Service;

use DateTime;
use Exception;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Module\Hc\Factory\Slave;
use GibsonOS\Module\Hc\Model\Log as LogModel;
use GibsonOS\Module\Hc\Model\Master as MasterModel;
use GibsonOS\Module\Hc\Repository\Module as ModuleRepository;
use GibsonOS\Module\Hc\Utility\Transform;

class Master extends AbstractService
{
    const TYPE_RECEIVE_RETURN = 0;
    const TYPE_HANDSHAKE = 1;
    const TYPE_STATUS = 2;
    const TYPE_NEW_SLAVE = 3;
    const TYPE_SLAVE_IS_HC = 4;
    const TYPE_SCAN_BUS = 5;
    const TYPE_DATA = 255;

    /**
     * @var MasterModel
     */
    private $master;
    /**
     * @var Server
     */
    private $server;
    /**
     * @var Event
     */
    private $event;

    /**
     * Master constructor.
     * @param MasterModel $master
     * @param Server $server
     * @param Event $event
     */
    public function __construct(MasterModel $master, Server $server, Event $event)
    {
        $this->master = $master;
        $this->server = $server;
        $this->event = $event;
    }

    /**
     * @param int $type
     * @param string $data
     * @throws FileNotFound
     * @throws SaveError
     * @throws SelectError
     * @throws Exception
     */
    public function receive($type, $data)
    {
        $log = (new LogModel())
            ->setMasterId($this->getModel()->getId())
            ->setType($type)
            ->setData(Transform::asciiToHex($data))
            ->setDirection(Server::DIRECTION_INPUT);

        $address = Transform::asciiToInt($data, 0);
        $command = Transform::asciiToInt($data, 1);
        $data = substr($data, 2);

        echo 'Type: ' . $type . PHP_EOL;
        echo 'Command: ' . $command . PHP_EOL;
        if ($type === Master::TYPE_NEW_SLAVE) {
            $log->save();

            echo 'New Slave ' . $address . PHP_EOL;
            try {
                $slaveModel = ModuleRepository::getByAddress($address, $this->master->getId());
                $slave = Slave::create($slaveModel, $this);
            } catch (SelectError $exception) {
                try {
                    $slave = Slave::createByDefaultAddress($address, $this);
                } catch (SelectError $e) {
                    $slave = Slave::createBlank($address, $this);
                }

                $slave->getModel()->setName('Neues Modul');
            }

            $slave->handshake();
        } else {
            $slaveModel = ModuleRepository::getByAddress($address, $this->master->getId());
            $slave = Slave::create($slaveModel, $this);
            $slave->receive($type, $command, $data);

            $log
                ->setModuleId($slave->getModel()->getId())
                ->setCommand($command)
                ->setData(Transform::asciiToHex($data))
                ->save();
        }

        $slave->getModel()->setOffline(0);
        $slave->getModel()->setModified(new DateTime());
        $slave->getModel()->setMaster($this->getModel());
        $slave->getModel()->save();
    }

    /**
     * @return MasterModel
     */
    public function getModel()
    {
        return $this->master;
    }

    /**
     * @param int $type
     * @param string $data
     * @throws AbstractException
     */
    public function send($type, $data)
    {
        $this->server->send($this->master->getAddress(), $type, $data);
    }

    /**
     * @param int $address
     * @throws AbstractException
     * @throws SaveError
     */
    public function setAddress($address)
    {
        try {
            $data = $this->master->getName() . chr($address);
            $this->send(Master::TYPE_HANDSHAKE, $data);
            $this->receiveReceiveReturn();

            (new LogModel())
                ->setMasterId($this->getModel()->getId())
                ->setType(Master::TYPE_HANDSHAKE)
                ->setData(Transform::asciiToHex($data))
                ->setDirection(Server::DIRECTION_OUTPUT)
                ->save();
        } catch (AbstractException $exception) {
            throw $exception;
        }

        $this->master->setAddress($address);
        $this->master->save();
    }

    /**
     * @throws AbstractException
     */
    public function scanBus()
    {
        $this->send(self::TYPE_SCAN_BUS, '');
        $this->receiveReceiveReturn();
    }

    /**
     * @param int $address
     * @param int $type
     * @param int $command
     * @return string
     * @throws ReceiveError
     */
    public function receiveReadData($address, $type, $command)
    {
        $data = $this->server->receiveReadData($this->master->getAddress(), $type);

        if ($address != Transform::asciiToInt($data, 0)) {
            new ReceiveError('Slave Adresse stimmt nicht überein!');
        }

        if ($command != Transform::asciiToInt($data, 1)) {
            new ReceiveError('Kommando stimmt nicht überein!');
        }

        return substr($data, 2);
    }

    /**
     *
     */
    public function receiveReceiveReturn()
    {
        $this->server->receiveReceiveReturn($this->master->getAddress());
    }
}