<?php
namespace GibsonOS\Module\Hc\Service\Event;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Factory\Slave;
use GibsonOS\Module\Hc\Model\Event\Element as ElementModel;
use GibsonOS\Module\Hc\Repository\Type;
use GibsonOS\Module\Hc\Service\Event\Describer\DescriberInterface;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;

abstract class AbstractHc extends AbstractEvent
{
    /**
     * @var AbstractHcSlave
     */
    protected $slave;

    /**
     * AbstractHc constructor.
     * @param ElementModel $element
     * @param DescriberInterface $describer
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
     * @param array $params
     * @throws AbstractException
     */
    public function writeAddress(array $params): void
    {
        $this->slave->writeAddress($params['address']);
    }

    /**
     * @return int
     * @throws AbstractException
     */
    public function readDeviceId(): int
    {
        return $this->slave->readDeviceId();
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function writeDeviceId(array $params): void
    {
        $this->slave->writeDeviceId($params['deviceId']);
    }

    /**
     * @return int
     * @throws AbstractException
     */
    public function readTypeId(): int
    {
        return $this->slave->readTypeId();
    }

    /**
     * @param array $params
     * @throws AbstractException
     * @throws SelectError
     */
    public function writeTypeId(array $params): void
    {
        $type = Type::getById($params['typeId']);
        $this->slave->writeType($type);
    }

    /**
     * @throws AbstractException
     */
    public function writeRestart(): void
    {
        $this->slave->writeRestart();
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readHertz(): int
    {
        return $this->slave->readHertz();
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readEepromSize(): int
    {
        return $this->slave->readEepromSize();
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readEepromFree(): int
    {
        return $this->slave->readEepromFree();
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readEepromPosition(): int
    {
        return $this->slave->readEepromPosition();
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function writeEepromPosition($params): void
    {
        $this->slave->writeEepromPosition($params['position']);
    }

    /**
     * @throws AbstractException
     */
    public function writeEepromErase(): void
    {
        $this->slave->writeEepromErase();
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readBufferSize(): int
    {
        return $this->slave->readBufferSize();
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readPwmSpeed(): int
    {
        return $this->slave->readPwmSpeed();
    }

    /**
     * @param int $pwmSpeed
     * @throws AbstractException
     */
    public function writePwmSpeed(int $pwmSpeed): void
    {
        $this->slave->writePwmSpeed($pwmSpeed);
    }

    /**
     * @return array
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readLedStatus(): array
    {
        return $this->slave->readLedStatus();
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function writePowerLed(array $params): void
    {
        $this->slave->writePowerLed($params['on']);
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function writeErrorLed(array $params): void
    {
        $this->slave->writeErrorLed($params['on']);
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function writeConnectLed(array $params): void
    {
        $this->slave->writeConnectLed($params['on']);
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function writeTransreceiveLed(array $params): void
    {
        $this->slave->writeTransreceiveLed($params['on']);
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function writeTransceiveLed(array $params): void
    {
        $this->slave->writeTransceiveLed($params['on']);
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function writeReceiveLed(array $params): void
    {
        $this->slave->writeReceiveLed($params['on']);
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function writeCustomLed(array $params): void
    {
        $this->slave->writeCustomLed($params['on']);
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readPowerLed(): bool
    {
        return $this->slave->readPowerLed();
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readErrorLed(): bool
    {
        return $this->slave->readErrorLed();
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readConnectLed(): bool
    {
        return $this->slave->readConnectLed();
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readTransreceiveLed(): bool
    {
        return $this->slave->readTransreceiveLed();
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readTransceiveLed(): bool
    {
        return $this->slave->readTransceiveLed();
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readReceiveLed(): bool
    {
        return $this->slave->readReceiveLed();
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readCustomLed(): bool
    {
        return $this->slave->readCustomLed();
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function writeRgbLed(array $params): void
    {
        $this->slave->writeRgbLed(
            $params[AbstractHcSlave::POWER_LED_KEY],
            $params[AbstractHcSlave::ERROR_LED_KEY],
            $params[AbstractHcSlave::CONNECT_LED_KEY],
            $params[AbstractHcSlave::TRANSCEIVE_LED_KEY],
            $params[AbstractHcSlave::RECEIVE_LED_KEY],
            $params[AbstractHcSlave::CUSTOM_LED_KEY]
        );
    }

    /**
     * @return array
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readRgbLed(): array
    {
        return $this->slave->readRgbLed();
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function writeAllLeds(array $params): void
    {
        $this->slave->writeAllLeds(
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
     * @return array
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readAllLeds(): array
    {
        return $this->slave->readAllLeds();
    }
}