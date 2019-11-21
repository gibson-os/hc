<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Event;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Factory\Slave;
use GibsonOS\Module\Hc\Model\Event\Element as ElementModel;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Type;
use GibsonOS\Module\Hc\Service\Event\Describer\DescriberInterface;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;

abstract class AbstractHcService extends AbstractEventService
{
    /**
     * @var AbstractHcSlave
     */
    protected $slave;

    /**
     * AbstractHc constructor.
     *
     * @param ElementModel       $element
     * @param DescriberInterface $describer
     *
     * @throws FileNotFound
     * @throws GetError
     * @throws SelectError
     */
    public function __construct(ElementModel $element, DescriberInterface $describer)
    {
        parent::__construct($element, $describer);

        $this->slave = Slave::createBySlaveId($element->getModuleId());
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeAddress(Module $slave, array $params): void
    {
        $this->slave->writeAddress($slave, $params['address']);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return int
     */
    public function readDeviceId(Module $slave): int
    {
        return $this->slave->readDeviceId($slave);
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     */
    public function writeDeviceId(Module $slave, array $params): void
    {
        $this->slave->writeDeviceId($slave, $params['deviceId']);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return int
     */
    public function readTypeId(Module $slave): int
    {
        return $this->slave->readTypeId($slave);
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     * @throws FileNotFound
     * @throws SaveError
     * @throws SelectError
     */
    public function writeTypeId(Module $slave, array $params): void
    {
        $type = Type::getById($params['typeId']);
        $this->slave->writeType($slave, $type);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeRestart(Module $slave): void
    {
        $this->slave->writeRestart($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return int
     */
    public function readHertz(Module $slave): int
    {
        return $this->slave->readHertz($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return int
     */
    public function readEepromSize(Module $slave): int
    {
        return $this->slave->readEepromSize($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return int
     */
    public function readEepromFree(Module $slave): int
    {
        return $this->slave->readEepromFree($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return int
     */
    public function readEepromPosition(Module $slave): int
    {
        return $this->slave->readEepromPosition($slave);
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeEepromPosition(Module $slave, array $params): void
    {
        $this->slave->writeEepromPosition($slave, $params['position']);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeEepromErase(Module $slave): void
    {
        $this->slave->writeEepromErase($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return int
     */
    public function readBufferSize(Module $slave): int
    {
        return $this->slave->readBufferSize($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return int
     */
    public function readPwmSpeed(Module $slave): int
    {
        return $this->slave->readPwmSpeed($slave);
    }

    /**
     * @param Module $slave
     * @param int    $pwmSpeed
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writePwmSpeed(Module $slave, int $pwmSpeed): void
    {
        $this->slave->writePwmSpeed($slave, $pwmSpeed);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return array
     */
    public function readLedStatus(Module $slave): array
    {
        return $this->slave->readLedStatus($slave);
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writePowerLed(Module $slave, array $params): void
    {
        $this->slave->writePowerLed($slave, $params['on']);
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeErrorLed(Module $slave, array $params): void
    {
        $this->slave->writeErrorLed($slave, $params['on']);
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeConnectLed(Module $slave, array $params): void
    {
        $this->slave->writeConnectLed($slave, $params['on']);
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeTransreceiveLed(Module $slave, array $params): void
    {
        $this->slave->writeTransreceiveLed($slave, $params['on']);
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeTransceiveLed(Module $slave, array $params): void
    {
        $this->slave->writeTransceiveLed($slave, $params['on']);
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeReceiveLed(Module $slave, array $params): void
    {
        $this->slave->writeReceiveLed($slave, $params['on']);
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeCustomLed(Module $slave, array $params): void
    {
        $this->slave->writeCustomLed($slave, $params['on']);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return bool
     */
    public function readPowerLed(Module $slave): bool
    {
        return $this->slave->readPowerLed($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return bool
     */
    public function readErrorLed(Module $slave): bool
    {
        return $this->slave->readErrorLed($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return bool
     */
    public function readConnectLed(Module $slave): bool
    {
        return $this->slave->readConnectLed($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return bool
     */
    public function readTransreceiveLed(Module $slave): bool
    {
        return $this->slave->readTransreceiveLed($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return bool
     */
    public function readTransceiveLed(Module $slave): bool
    {
        return $this->slave->readTransceiveLed($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return bool
     */
    public function readReceiveLed(Module $slave): bool
    {
        return $this->slave->readReceiveLed($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return bool
     */
    public function readCustomLed(Module $slave): bool
    {
        return $this->slave->readCustomLed($slave);
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeRgbLed(Module $slave, array $params): void
    {
        $this->slave->writeRgbLed(
            $slave,
            $params[AbstractHcSlave::POWER_LED_KEY],
            $params[AbstractHcSlave::ERROR_LED_KEY],
            $params[AbstractHcSlave::CONNECT_LED_KEY],
            $params[AbstractHcSlave::TRANSCEIVE_LED_KEY],
            $params[AbstractHcSlave::RECEIVE_LED_KEY],
            $params[AbstractHcSlave::CUSTOM_LED_KEY]
        );
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return array
     */
    public function readRgbLed(Module $slave): array
    {
        return $this->slave->readRgbLed($slave);
    }

    /**
     * @param Module $slave
     * @param array  $params
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeAllLeds(Module $slave, array $params): void
    {
        $this->slave->writeAllLeds(
            $slave,
            $params[AbstractHcSlave::POWER_LED_KEY],
            $params[AbstractHcSlave::ERROR_LED_KEY],
            $params[AbstractHcSlave::CONNECT_LED_KEY],
            $params[AbstractHcSlave::TRANSRECEIVE_LED_KEY],
            $params[AbstractHcSlave::TRANSCEIVE_LED_KEY],
            $params[AbstractHcSlave::RECEIVE_LED_KEY],
            $params[AbstractHcSlave::CUSTOM_LED_KEY]
        );
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return array
     */
    public function readAllLeds(Module $slave): array
    {
        return $this->slave->readAllLeds($slave);
    }
}
