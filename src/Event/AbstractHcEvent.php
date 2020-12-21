<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Event\AbstractEvent;
use GibsonOS\Core\Event\Describer\DescriberInterface;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\ServiceManagerService;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;

abstract class AbstractHcEvent extends AbstractEvent
{
    private TypeRepository $typeRepository;

    public function __construct(
        DescriberInterface $describer,
        ServiceManagerService $serviceManagerService,
        TypeRepository $typeRepository
    ) {
        parent::__construct($describer, $serviceManagerService);
        $this->typeRepository = $typeRepository;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeAddress(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeAddress($slave, $params['address']);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readDeviceId(AbstractHcSlave $slaveService, Module $slave): int
    {
        return $slaveService->readDeviceId($slave);
    }

    /**
     * @throws AbstractException
     */
    public function writeDeviceId(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeDeviceId($slave, $params['deviceId']);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readTypeId(AbstractHcSlave $slaveService, Module $slave): int
    {
        return $slaveService->readTypeId($slave);
    }

    /**
     * @throws AbstractException
     * @throws FileNotFound
     * @throws SaveError
     * @throws SelectError
     */
    public function writeTypeId(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $type = $this->typeRepository->getById($params['typeId']);
        $slaveService->writeTypeId($slave, $type);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeRestart(AbstractHcSlave $slaveService, Module $slave): void
    {
        $slaveService->writeRestart($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readHertz(AbstractHcSlave $slaveService, Module $slave): int
    {
        return $slaveService->readHertz($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readEepromSize(AbstractHcSlave $slaveService, Module $slave): int
    {
        return $slaveService->readEepromSize($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readEepromFree(AbstractHcSlave $slaveService, Module $slave): int
    {
        return $slaveService->readEepromFree($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readEepromPosition(AbstractHcSlave $slaveService, Module $slave): int
    {
        return $slaveService->readEepromPosition($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeEepromPosition(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeEepromPosition($slave, $params['position']);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeEepromErase(AbstractHcSlave $slaveService, Module $slave): void
    {
        $slaveService->writeEepromErase($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readBufferSize(AbstractHcSlave $slaveService, Module $slave): int
    {
        return $slaveService->readBufferSize($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readPwmSpeed(AbstractHcSlave $slaveService, Module $slave): int
    {
        return $slaveService->readPwmSpeed($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writePwmSpeed(AbstractHcSlave $slaveService, Module $slave, int $pwmSpeed): void
    {
        $slaveService->writePwmSpeed($slave, $pwmSpeed);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readLedStatus(AbstractHcSlave $slaveService, Module $slave): array
    {
        return $slaveService->readLedStatus($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writePowerLed(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writePowerLed($slave, $params['on']);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeErrorLed(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeErrorLed($slave, $params['on']);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeConnectLed(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeConnectLed($slave, $params['on']);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeTransreceiveLed(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeTransreceiveLed($slave, $params['on']);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeTransceiveLed(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeTransceiveLed($slave, $params['on']);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeReceiveLed(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeReceiveLed($slave, $params['on']);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeCustomLed(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeCustomLed($slave, $params['on']);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readPowerLed(AbstractHcSlave $slaveService, Module $slave): bool
    {
        return $slaveService->readPowerLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readErrorLed(AbstractHcSlave $slaveService, Module $slave): bool
    {
        return $slaveService->readErrorLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readConnectLed(AbstractHcSlave $slaveService, Module $slave): bool
    {
        return $slaveService->readConnectLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readTransreceiveLed(AbstractHcSlave $slaveService, Module $slave): bool
    {
        return $slaveService->readTransreceiveLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readTransceiveLed(AbstractHcSlave $slaveService, Module $slave): bool
    {
        return $slaveService->readTransceiveLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readReceiveLed(AbstractHcSlave $slaveService, Module $slave): bool
    {
        return $slaveService->readReceiveLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readCustomLed(AbstractHcSlave $slaveService, Module $slave): bool
    {
        return $slaveService->readCustomLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeRgbLed(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeRgbLed(
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
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readRgbLed(AbstractHcSlave $slaveService, Module $slave): array
    {
        return $slaveService->readRgbLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeAllLeds(AbstractHcSlave $slaveService, Module $slave, array $params): void
    {
        $slaveService->writeAllLeds(
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
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readAllLeds(AbstractHcSlave $slaveService, Module $slave): array
    {
        return $slaveService->readAllLeds($slave);
    }
}
