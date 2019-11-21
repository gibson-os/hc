<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Model\Log as LogModel;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\EventService;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\ServerService;
use GibsonOS\Module\Hc\Service\TransformService;

abstract class AbstractSlave extends AbstractService
{
    const READ_BIT = 1;

    const WRITE_BIT = 0;

    /**
     * @var MasterService
     */
    protected $master;

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

    /**
     * @var TransformService
     */
    protected $transform;

    /**
     * @var EventService
     */
    protected $event;

    /**
     * @param Module $slave
     *
     * @return Module
     */
    abstract public function handshake(Module $slave): Module;

    /**
     * Slave constructor.
     *
     * @param MasterService    $master
     * @param EventService     $event
     * @param TransformService $transform
     * @param array            $attributes
     */
    public function __construct(MasterService $master, EventService $event, TransformService $transform, array $attributes = [])
    {
        $this->master = $master;
        $this->event = $event;
        $this->attributes = $attributes;
        $this->transform = $transform;
    }

    /**
     * @param Module $slave
     * @param int    $command
     * @param string $data
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function write(Module $slave, int $command, string $data): void
    {
        $this->master->send(
            $slave->getMaster(),
            MasterService::TYPE_DATA,
            chr($this->getAddressWithReadWriteBit($slave, self::WRITE_BIT)) . chr($command) . $data
        );
        $this->master->receiveReceiveReturn($slave->getMaster());
        $this->addLog($slave, MasterService::TYPE_DATA, $command, $data, ServerService::DIRECTION_OUTPUT);
    }

    /**
     * @param Module $slave
     * @param int    $command
     * @param int    $length
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return string
     */
    public function read(Module $slave, int $command, int $length): string
    {
        $this->master->send(
            $slave->getMaster(),
            MasterService::TYPE_DATA,
            chr($this->getAddressWithReadWriteBit($slave, self::READ_BIT)) . chr($command) . chr($length)
        );

        $data = $this->master->receiveReadData(
            $slave->getMaster(),
            (int) $slave->getAddress(),
            MasterService::TYPE_DATA,
            $command
        );
        $this->addLog($slave, MasterService::TYPE_DATA, $command, $data, ServerService::DIRECTION_INPUT);

        return $data;
    }

    /**
     * @param Module $slave
     * @param int    $bit
     *
     * @return int
     */
    private function getAddressWithReadWriteBit(Module $slave, int $bit): int
    {
        return ($slave->getAddress() << 1) | $bit;
    }

    /**
     * @param Module $slave
     * @param int    $type
     * @param int    $command
     * @param string $data
     * @param string $direction
     *
     * @throws SaveError
     * @throws DateTimeError
     */
    private function addLog(Module $slave, int $type, int $command, string $data, string $direction): void
    {
        (new LogModel())
            ->setMasterId($slave->getMaster()->getId())
            ->setModuleId($slave->getId())
            ->setSlaveAddress($slave->getAddress())
            ->setType($type)
            ->setCommand($command)
            ->setData($this->transform->asciiToHex($data))
            ->setDirection($direction)
            ->save();
    }
}
