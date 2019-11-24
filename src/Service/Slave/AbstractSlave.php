<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\MasterService;
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
     * @var TransformService
     */
    protected $transform;

    abstract public function handshake(Module $slave): Module;

    /**
     * Slave constructor.
     */
    public function __construct(
        MasterService $master,
        TransformService $transform
    ) {
        $this->master = $master;
        $this->transform = $transform;
    }

    /**
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
        $this->addLog($slave, MasterService::TYPE_DATA, $command, $data, Log::DIRECTION_OUTPUT);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
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
        $this->addLog($slave, MasterService::TYPE_DATA, $command, $data, Log::DIRECTION_INPUT);

        return $data;
    }

    private function getAddressWithReadWriteBit(Module $slave, int $bit): int
    {
        return ($slave->getAddress() << 1) | $bit;
    }

    /**
     * @throws SaveError
     * @throws DateTimeError
     * @throws GetError
     */
    private function addLog(Module $slave, int $type, int $command, string $data, string $direction): void
    {
        (new Log())
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
