<?php
namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Model\Log as LogModel;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\Event;
use GibsonOS\Module\Hc\Service\Master;
use GibsonOS\Module\Hc\Service\Server;
use GibsonOS\Module\Hc\Utility\Transform;

abstract class AbstractSlave extends AbstractService
{
    const READ_BIT = 1;
    const WRITE_BIT = 0;

    /**
     * @var Module
     */
    protected $slave;
    /**
     * @var Master
     */
    protected $master;
    /**
     * @var Event
     */
    protected $event;
    /**
     * @var string|null
     */
    private $data;
    /**
     * @var int
     */
    private $command;
    /**
     * @var int
     */
    private $type;
    /**
     * @var array
     */
    protected $attributes;

    abstract public function handshake(): void;

    /**
     * Slave constructor.
     * @param Module $slaveModel
     * @param Master $master
     * @param Event $event
     * @param array $attributes
     */
    public function __construct(Module $slaveModel, Master $master, Event $event, array $attributes = [])
    {
        $this->slave = $slaveModel;
        $this->master = $master;
        $this->event = $event;
        $this->attributes = $attributes;
    }

    /**
     * @param int $type
     * @param int $command
     * @param null|string $data
     */
    public function receive(int $type, int $command, string $data = null): void
    {
        $this->type = $type;
        $this->command = $command;
        $this->data = $data;
    }

    /**
     * @param int $command
     * @param string $data
     * @throws AbstractException
     */
    public function write(int $command, string $data): void
    {
        $this->master->send(
            Master::TYPE_DATA,
            chr($this->getAddressWithReadWriteBit(self::WRITE_BIT)) . chr($command) . $data
        );
        $this->master->receiveReceiveReturn();
        $this->addLog(Master::TYPE_DATA, $command, $data, Server::DIRECTION_OUTPUT);
    }

    /**
     * @param int $command
     * @param int $length
     * @return string
     * @throws ReceiveError
     * @throws AbstractException
     */
    public function read(int $command, int $length): string
    {
        $this->master->send(
            Master::TYPE_DATA,
            chr($this->getAddressWithReadWriteBit(self::READ_BIT)) . chr($command) . chr($length)
        );

        $data = $this->master->receiveReadData($this->slave->getAddress(), Master::TYPE_DATA, $command);
        $this->addLog(Master::TYPE_DATA, $command, $data, Server::DIRECTION_INPUT);

        return $data;
    }

    /**
     * @return Module
     */
    public function getModel(): Module
    {
        return $this->slave;
    }

    /**
     * @param int $bit
     * @return int
     */
    private function getAddressWithReadWriteBit(int $bit): int
    {
        return ($this->slave->getAddress()<<1) | $bit;
    }

    /**
     * @return null|string
     */
    public function getData(): ?string
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getCommand(): int
    {
        return $this->command;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * @param int $type
     * @param int $command
     * @param string $data
     * @param string $direction
     * @throws SaveError
     */
    private function addLog(int $type, int $command, string $data, string $direction): void
    {
        (new LogModel())
            ->setMasterId($this->master->getModel()->getId())
            ->setModuleId($this->slave->getId())
            ->setSlaveAddress($this->slave->getAddress())
            ->setType($type)
            ->setCommand($command)
            ->setData(Transform::asciiToHex($data))
            ->setDirection($direction)
            ->save();
    }
}