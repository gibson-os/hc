<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Attribute\Event;
use GibsonOS\Core\Dto\Parameter\BoolParameter;
use GibsonOS\Core\Dto\Parameter\IntParameter;
use GibsonOS\Core\Dto\Parameter\StringParameter;
use GibsonOS\Core\Event\AbstractEvent;
use GibsonOS\Core\Event\Describer\DescriberInterface;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\ServiceManagerService;
use GibsonOS\Module\Hc\Dto\Parameter\SlaveParameter;
use GibsonOS\Module\Hc\Dto\Parameter\TypeParameter;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;
use Psr\Log\LoggerInterface;

abstract class AbstractHcEvent extends AbstractEvent
{
    #[Event\Trigger('Vor setzen der Adresse', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'newAddress', 'className' => IntParameter::class, 'title' => 'Neue Adresse'],
    ])]
    public const BEFORE_WRITE_ADDRESS = 'beforeWriteAddress';

    #[Event\Trigger('Nach setzen der Adresse', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'newAddress', 'className' => IntParameter::class, 'title' => 'Neue Adresse'],
    ])]
    public const AFTER_WRITE_ADDRESS = 'afterWriteAddress';

    #[Event\Trigger('Device ID gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'deviceId', 'className' => IntParameter::class, 'title' => 'Device ID'],
    ])]
    public const READ_DEVICE_ID = 'readDeviceId';

    #[Event\Trigger('Vor setzen der Device ID', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'deviceId', 'className' => IntParameter::class, 'title' => 'Device ID'],
    ])]
    public const BEFORE_WRITE_DEVICE_ID = 'beforeWriteDeviceId';

    #[Event\Trigger('Nach setzen der Device ID', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'deviceId', 'className' => IntParameter::class, 'title' => 'Device ID'],
    ])]
    public const AFTER_WRITE_DEVICE_ID = 'afterWriteDeviceId';

    #[Event\Trigger('Typ gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'typeId', 'className' => TypeParameter::class],
    ])]
    public const READ_TYPE_ID = 'readTypeId';

    #[Event\Trigger('Vor setzen des Types', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'typeId', 'className' => TypeParameter::class],
    ])]
    public const BEFORE_WRITE_TYPE_ID = 'beforeWriteTypeId';

    #[Event\Trigger('Nach setzen des Types', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'typeId', 'className' => TypeParameter::class],
    ])]
    public const AFTER_WRITE_TYPE_ID = 'afterWriteTypeId';

    #[Event\Trigger('Vor Neustart', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const BEFORE_WRITE_RESTART = 'beforeWriteRestart';

    #[Event\Trigger('Nach Neustart', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const AFTER_WRITE_RESTART = 'afterWriteRestart';

    #[Event\Trigger('Konfiguration gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'config', 'className' => StringParameter::class, 'title' => 'Konfiguration'],
    ])]
    public const READ_CONFIG = 'readConfig';

    #[Event\Trigger('Hertz gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'hertz', 'className' => IntParameter::class, 'title' => 'Hertz'],
    ])]
    public const READ_HERTZ = 'readHertz';

    #[Event\Trigger('PWM Speed gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'speed', 'className' => IntParameter::class, 'title' => 'PWM Hertz'],
    ])]
    public const READ_PWM_SPEED = 'readPwmSpeed';

    #[Event\Trigger('Vor PWM Speed setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'speed', 'className' => IntParameter::class, 'title' => 'PWM Hertz'],
    ])]
    public const BEFORE_WRITE_PWM_SPEED = 'beforeWritePwmSpeed';

    #[Event\Trigger('Nach PWM Speed setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'speed', 'className' => IntParameter::class, 'title' => 'PWM Hertz'],
    ])]
    public const AFTER_WRITE_PWM_SPEED = 'afterWritePwmSpeed';

    #[Event\Trigger('EEPROM Größe gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'eepromSize', 'className' => IntParameter::class, 'title' => 'EEPROM Größe'],
    ])]
    public const READ_EEPROM_SIZE = 'readEepromSize';

    #[Event\Trigger('Freier EEPROM Platz gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'eepromFree', 'className' => IntParameter::class, 'title' => 'Freier EEPROM'],
    ])]
    public const READ_EEPROM_FREE = 'readEepromFree';

    #[Event\Trigger('EEPROM Position gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'eepromPosition', 'className' => IntParameter::class, 'title' => 'EEPROM Position'],
    ])]
    public const READ_EEPROM_POSITION = 'readEepromPosition';

    #[Event\Trigger('Vor EEPROM Position setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'eepromPosition', 'className' => IntParameter::class, 'title' => 'EEPROM Position'],
    ])]
    public const BEFORE_WRITE_EEPROM_POSITION = 'beforeWriteEepromPosition';

    #[Event\Trigger('Nach EEPROM Position setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'eepromPosition', 'className' => IntParameter::class, 'title' => 'EEPROM Position'],
    ])]
    public const AFTER_WRITE_EEPROM_POSITION = 'afterWriteEepromPosition';

    #[Event\Trigger('Vor EEPROM formatieren', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const BEFORE_WRITE_EEPROM_ERASE = 'beforeWriteEepromErase';

    #[Event\Trigger('Nach EEPROM formatieren', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const AFTER_WRITE_EEPROM_ERASE = 'afterWriteEepromErase';

    #[Event\Trigger('Buffer Größe gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'bufferSize', 'className' => IntParameter::class, 'title' => 'Buffer Größe'],
    ])]
    public const READ_BUFFER_SIZE = 'readBufferSize';

    #[Event\Trigger('LED Status gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => AbstractHcSlave::POWER_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Power LED'],
        ['key' => AbstractHcSlave::ERROR_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Error LED'],
        ['key' => AbstractHcSlave::CONNECT_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Connect LED'],
        ['key' => AbstractHcSlave::TRANSRECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transreceive LED'],
        ['key' => AbstractHcSlave::TRANSCEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transceive LED'],
        ['key' => AbstractHcSlave::RECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Receive LED'],
        ['key' => AbstractHcSlave::CUSTOM_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Custom LED'],
        ['key' => AbstractHcSlave::RGB_LED_KEY, 'className' => BoolParameter::class, 'title' => 'RGB LED'],
    ])]
    public const READ_LED_STATUS = 'readLedStatus';

    #[Event\Trigger('Vor Power LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_POWER_LED = 'beforeWritePowerLed';

    #[Event\Trigger('Nach Power LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_POWER_LED = 'afterWritePowerLed';

    #[Event\Trigger('Vor Error LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_ERROR_LED = 'beforeWriteErrorLed';

    #[Event\Trigger('Nach Error LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_ERROR_LED = 'afterWriteErrorLed';

    #[Event\Trigger('Vor Connect LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_CONNECT_LED = 'beforeWriteConnectLed';

    #[Event\Trigger('Nach Connect LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_CONNECT_LED = 'afterWriteConnectLed';

    #[Event\Trigger('Vor Transreceive LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_TRANSRECEIVE_LED = 'beforeWriteTransreceiveLed';

    #[Event\Trigger('Nach Transreceive LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_TRANSRECEIVE_LED = 'afterWriteTransreceiveLed';

    #[Event\Trigger('Vor Transceive LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_TRANSCEIVE_LED = 'beforeWriteTransceiveLed';

    #[Event\Trigger('Nach Transceive LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_TRANSCEIVE_LED = 'afterWriteTransceiveLed';

    #[Event\Trigger('Vor Receive LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_RECEIVE_LED = 'beforeWriteReceiveLed';

    #[Event\Trigger('nach Receive LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_RECEIVE_LED = 'afterWriteReceiveLed';

    #[Event\Trigger('Vor Custom LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_CUSTOM_LED = 'beforeWriteCustomLed';

    #[Event\Trigger('Nach Custom LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_CUSTOM_LED = 'afterWriteCustomLed';

    #[Event\Trigger('Power LED gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_POWER_LED = 'readPowerLed';

    #[Event\Trigger('Error LED gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_ERROR_LED = 'readErrorLed';

    #[Event\Trigger('Connect LED gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_CONNECT_LED = 'readConnectLed';

    #[Event\Trigger('Transreceive LED gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_TRANSRECEIVE_LED = 'readTransreceiveLed';

    #[Event\Trigger('Transceive LED gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_TRANSCEIVE_LED = 'readTransceiveLed';

    #[Event\Trigger('Receive LED gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_RECEIVE_LED = 'readReceiveLed';

    #[Event\Trigger('Custom LED gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_CUSTOM_LED = 'readCustomLed';

    #[Event\Trigger('Vor RGB LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => AbstractHcSlave::POWER_LED_KEY, 'className' => StringParameter::class, 'Power LED'],
        ['key' => AbstractHcSlave::ERROR_LED_KEY, 'className' => StringParameter::class, 'Error LED'],
        ['key' => AbstractHcSlave::CONNECT_LED_KEY, 'className' => StringParameter::class, 'Connect LED'],
        ['key' => AbstractHcSlave::TRANSCEIVE_LED_KEY, 'className' => StringParameter::class, 'Transceive LED'],
        ['key' => AbstractHcSlave::RECEIVE_LED_KEY, 'className' => StringParameter::class, 'Receive LED'],
        ['key' => AbstractHcSlave::CUSTOM_LED_KEY, 'className' => StringParameter::class, 'Custom LED'],
    ])]
    public const BEFORE_WRITE_RGB_LED = 'beforeWriteRgbLed';

    #[Event\Trigger('Nach RGB LED setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => AbstractHcSlave::POWER_LED_KEY, 'className' => StringParameter::class, 'Power LED'],
        ['key' => AbstractHcSlave::ERROR_LED_KEY, 'className' => StringParameter::class, 'Error LED'],
        ['key' => AbstractHcSlave::CONNECT_LED_KEY, 'className' => StringParameter::class, 'Connect LED'],
        ['key' => AbstractHcSlave::TRANSCEIVE_LED_KEY, 'className' => StringParameter::class, 'Transceive LED'],
        ['key' => AbstractHcSlave::RECEIVE_LED_KEY, 'className' => StringParameter::class, 'Receive LED'],
        ['key' => AbstractHcSlave::CUSTOM_LED_KEY, 'className' => StringParameter::class, 'Custom LED'],
    ])]
    public const AFTER_WRITE_RGB_LED = 'afterWriteRgbLed';

    #[Event\Trigger('RGB LED gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => AbstractHcSlave::POWER_LED_KEY, 'className' => StringParameter::class, 'Power LED'],
        ['key' => AbstractHcSlave::ERROR_LED_KEY, 'className' => StringParameter::class, 'Error LED'],
        ['key' => AbstractHcSlave::CONNECT_LED_KEY, 'className' => StringParameter::class, 'Connect LED'],
        ['key' => AbstractHcSlave::TRANSCEIVE_LED_KEY, 'className' => StringParameter::class, 'Transceive LED'],
        ['key' => AbstractHcSlave::RECEIVE_LED_KEY, 'className' => StringParameter::class, 'Receive LED'],
        ['key' => AbstractHcSlave::CUSTOM_LED_KEY, 'className' => StringParameter::class, 'Custom LED'],
    ])]
    public const READ_RGB_LED = 'readRgbLed';

    #[Event\Trigger('Alle LEDs gelesen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => AbstractHcSlave::POWER_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Power LED'],
        ['key' => AbstractHcSlave::ERROR_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Error LED'],
        ['key' => AbstractHcSlave::CONNECT_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Connect LED'],
        ['key' => AbstractHcSlave::TRANSRECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transreceive LED'],
        ['key' => AbstractHcSlave::TRANSCEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transceive LED'],
        ['key' => AbstractHcSlave::RECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Receive LED'],
        ['key' => AbstractHcSlave::CUSTOM_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Custom LED'],
    ])]
    public const READ_ALL_LEDS = 'readAllLeds';

    #[Event\Trigger('Vor alle LEDs setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => AbstractHcSlave::POWER_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Power LED'],
        ['key' => AbstractHcSlave::ERROR_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Error LED'],
        ['key' => AbstractHcSlave::CONNECT_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Connect LED'],
        ['key' => AbstractHcSlave::TRANSRECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transreceive LED'],
        ['key' => AbstractHcSlave::TRANSCEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transceive LED'],
        ['key' => AbstractHcSlave::RECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Receive LED'],
        ['key' => AbstractHcSlave::CUSTOM_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Custom LED'],
    ])]
    public const BEFORE_WRITE_ALL_LEDS = 'beforeWriteAllLeds';

    #[Event\Trigger('Nach alle LEDs setzen', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => AbstractHcSlave::POWER_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Power LED'],
        ['key' => AbstractHcSlave::ERROR_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Error LED'],
        ['key' => AbstractHcSlave::CONNECT_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Connect LED'],
        ['key' => AbstractHcSlave::TRANSRECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transreceive LED'],
        ['key' => AbstractHcSlave::TRANSCEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transceive LED'],
        ['key' => AbstractHcSlave::RECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Receive LED'],
        ['key' => AbstractHcSlave::CUSTOM_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Custom LED'],
        ['key' => AbstractHcSlave::RGB_LED_KEY, 'className' => BoolParameter::class, 'title' => 'RGB LED'],
    ])]
    public const AFTER_WRITE_ALL_LEDS = 'afterWriteAllLeds';

    public function __construct(
        DescriberInterface $describer,
        ServiceManagerService $serviceManagerService,
        private TypeRepository $typeRepository,
        protected LoggerInterface $logger
    ) {
        parent::__construct($describer, $serviceManagerService);
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
