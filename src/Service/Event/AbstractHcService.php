<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Event;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Type;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;

abstract class AbstractHcService extends AbstractEventService
{
    /**
     * @param AbstractHcSlave $slaveService
     * @param Module          $slave
     * @param array           $params
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeAddress(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeAddress($slave, $params['address']);
    }

    /**
     * @param AbstractHcSlave $slaveService
     * @param Module          $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return int
     */
    public function readDeviceId(AbstractHcSlave $slaveService, Module $slave): int
    {
        return $slaveService->readDeviceId($slave);
    }

    /**
     * @param AbstractHcSlave $slaveService
     * @param Module          $slave
     * @param array           $params
     *
     * @throws AbstractException
     */
    public function writeDeviceId(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeDeviceId($slave, $params['deviceId']);
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
    public function readTypeId(AbstractHcSlave $slaveService, Module $lsave): int
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
    public function writeTypeId(AbstractHcSlave $slaveService, Module $lsave, array $params): void
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
    public function writeRestart(AbstractHcSlave $slaveService, Module $lsave): void
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
    public function readHertz(AbstractHcSlave $slaveService, Module $lsave): int
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
    public function readEepromSize(AbstractHcSlave $slaveService, Module $lsave): int
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
    public function readEepromFree(AbstractHcSlave $slaveService, Module $lsave): int
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
    public function readEepromPosition(AbstractHcSlave $slaveService, Module $lsave): int
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
    public function writeEepromPosition(AbstractHcSlave $slaveService, Module $lsave, array $params): void
    {
        $this->slave->writeEepromPosition($slave, $params['position']);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeEepromErase(AbstractHcSlave $slaveService, Module $lsave): void
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
    public function readBufferSize(AbstractHcSlave $slaveService, Module $lsave): int
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
    public function readPwmSpeed(AbstractHcSlave $slaveService, Module $lsave): int
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
    public function writePwmSpeed(AbstractHcSlave $slaveService, Module $lsave, int $pwmSpeed): void
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
    public function readLedStatus(AbstractHcSlave $slaveService, Module $lsave): array
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
    public function writePowerLed(AbstractHcSlave $slaveService, Module $lsave, array $params): void
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
    public function writeErrorLed(AbstractHcSlave $slaveService, Module $lsave, array $params): void
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
    public function writeConnectLed(AbstractHcSlave $slaveService, Module $lsave, array $params): void
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
    public function writeTransreceiveLed(AbstractHcSlave $slaveService, Module $lsave, array $params): void
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
    public function writeTransceiveLed(AbstractHcSlave $slaveService, Module $lsave, array $params): void
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
    public function writeReceiveLed(AbstractHcSlave $slaveService, Module $lsave, array $params): void
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
    public function writeCustomLed(AbstractHcSlave $slaveService, Module $lsave, array $params): void
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
    public function readPowerLed(AbstractHcSlave $slaveService, Module $lsave): bool
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
    public function readErrorLed(AbstractHcSlave $slaveService, Module $lsave): bool
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
    public function readConnectLed(AbstractHcSlave $slaveService, Module $lsave): bool
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
    public function readTransreceiveLed(AbstractHcSlave $slaveService, Module $lsave): bool
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
    public function readTransceiveLed(AbstractHcSlave $slaveService, Module $lsave): bool
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
    public function readReceiveLed(AbstractHcSlave $slaveService, Module $lsave): bool
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
    public function readCustomLed(AbstractHcSlave $slaveService, Module $lsave): bool
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
    public function writeRgbLed(AbstractHcSlave $slaveService, Module $lsave, array $params): void
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
    public function readRgbLed(AbstractHcSlave $slaveService, Module $lsave): array
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
    public function writeAllLeds(AbstractHcSlave $slaveService, Module $lsave, array $params): void
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
    public function readAllLeds(AbstractHcSlave $slaveService, Module $lsave): array
    {
        return $this->slave->readAllLeds($slave);
    }
}
