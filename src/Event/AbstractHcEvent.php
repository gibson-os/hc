<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Attribute\Event;
use GibsonOS\Core\Dto\Parameter\BoolParameter;
use GibsonOS\Core\Dto\Parameter\IntParameter;
use GibsonOS\Core\Dto\Parameter\StringParameter;
use GibsonOS\Core\Event\AbstractEvent;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\EventException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Manager\ReflectionManager;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Dto\Parameter\SlaveParameter;
use GibsonOS\Module\Hc\Dto\Parameter\TypeParameter;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;
use JsonException;
use Psr\Log\LoggerInterface;
use ReflectionException;

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
        EventService $eventService,
        ReflectionManager $reflectionManager,
        private TypeRepository $typeRepository,
        protected LoggerInterface $logger,
        private AbstractHcSlave $slaveService
    ) {
        parent::__construct($eventService, $reflectionManager);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws WriteException
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Event\Method('Adresse schreiben')]
    public function writeAddress(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(IntParameter::class, 'Adresse')] int $address
    ): void {
        $this->slaveService->writeAddress($slave, $address);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Adresse lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Adresse')]
    public function readDeviceId(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): int {
        return $this->slaveService->readDeviceId($slave);
    }

    /**
     * @throws AbstractException
     */
    #[Event\Method('Device ID schreiben')]
    public function writeDeviceId(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(IntParameter::class, 'Device ID')] int $deviceId
    ): void {
        $this->slaveService->writeDeviceId($slave, $deviceId);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Typ lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Typ')]
    public function readTypeId(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): int {
        return $this->slaveService->readTypeId($slave);
    }

    /**
     * @throws AbstractException
     * @throws FileNotFound
     * @throws SaveError
     * @throws SelectError
     */
    #[Event\Method('Typ schreiben')]
    public function writeTypeId(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(IntParameter::class, 'Typ')] int $typeId
    ): void {
        $type = $this->typeRepository->getById($typeId);
        $this->slaveService->writeTypeId($slave, $type);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Neustarten')]
    public function writeRestart(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): void {
        $this->slaveService->writeRestart($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Hertz lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Hertz')]
    public function readHertz(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): int {
        return $this->slaveService->readHertz($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('EEPROM Größe lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Größe')]
    public function readEepromSize(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): int {
        return $this->slaveService->readEepromSize($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Freier Platz im EEPROM lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Freier Platz')]
    public function readEepromFree(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): int {
        return $this->slaveService->readEepromFree($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('EEPROM Zeigerposition lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Zeigerposition')]
    public function readEepromPosition(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): int {
        return $this->slaveService->readEepromPosition($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('EEPROM Zeigerposition schreiben')]
    public function writeEepromPosition(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(IntParameter::class, 'Position')] int $position
    ): void {
        $this->slaveService->writeEepromPosition($slave, $position);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Eeprom formatieren')]
    public function writeEepromErase(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
    ): void {
        $this->slaveService->writeEepromErase($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Buffer Größe lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Größe')]
    public function readBufferSize(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): int {
        return $this->slaveService->readBufferSize($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('PWM Geschwindigkeit lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Geschwindigkeit')]
    public function readPwmSpeed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): int {
        return $this->slaveService->readPwmSpeed($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('PWM Geschwindigkeit lesen')]
    public function writePwmSpeed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(IntParameter::class, 'Geschwindigkeit')] int $pwmSpeed
    ): void {
        $this->slaveService->writePwmSpeed($slave, $pwmSpeed);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('LED Status lesen')]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Power LED', key: AbstractHcSlave::POWER_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Error LED', key: AbstractHcSlave::ERROR_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Connect LED', key: AbstractHcSlave::CONNECT_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Transreceive LED', key: AbstractHcSlave::TRANSRECEIVE_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Transceive LED', key: AbstractHcSlave::TRANSCEIVE_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Receive LED', key: AbstractHcSlave::RECEIVE_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Custom LED', key: AbstractHcSlave::CUSTOM_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'RGB LED', key: AbstractHcSlave::RGB_LED_KEY)]
    public function readLedStatus(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): array {
        return $this->slaveService->readLedStatus($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Power LED schreiben')]
    public function writePowerLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')] bool $on
    ): void {
        $this->slaveService->writePowerLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Error LED schreiben')]
    public function writeErrorLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')] bool $on
    ): void {
        $this->slaveService->writeErrorLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Connect LED schreiben')]
    public function writeConnectLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')] bool $on
    ): void {
        $this->slaveService->writeConnectLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Tranreceive LED schreiben')]
    public function writeTransreceiveLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')] bool $on
    ): void {
        $this->slaveService->writeTransreceiveLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Transceive LED schreiben')]
    public function writeTransceiveLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')] bool $on
    ): void {
        $this->slaveService->writeTransceiveLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Receive LED schreiben')]
    public function writeReceiveLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')] bool $on
    ): void {
        $this->slaveService->writeReceiveLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Custom LED schreiben')]
    public function writeCustomLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')] bool $on
    ): void {
        $this->slaveService->writeCustomLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Power LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readPowerLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): bool {
        return $this->slaveService->readPowerLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Error LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readErrorLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): bool {
        return $this->slaveService->readErrorLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Connect LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readConnectLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): bool {
        return $this->slaveService->readConnectLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Transreceive LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readTransreceiveLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): bool {
        return $this->slaveService->readTransreceiveLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Transceive LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readTransceiveLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): bool {
        return $this->slaveService->readTransceiveLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Receive LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readReceiveLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): bool {
        return $this->slaveService->readReceiveLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Custom LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readCustomLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): bool {
        return $this->slaveService->readCustomLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('RGB LED schreiben')]
    public function writeRgbLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(StringParameter::class, 'Power LED')] string $power,
        #[Event\Parameter(StringParameter::class, 'Error LED')] string $error,
        #[Event\Parameter(StringParameter::class, 'Connect LED')] string $connect,
        #[Event\Parameter(StringParameter::class, 'Transceive LED')] string $transceive,
        #[Event\Parameter(StringParameter::class, 'Receive LED')] string $resceive,
        #[Event\Parameter(StringParameter::class, 'Custom LED')] string $custom,
    ): void {
        $this->slaveService->writeRgbLed(
            $slave,
            $power,
            $error,
            $connect,
            $transceive,
            $resceive,
            $custom
        );
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('RGB LED schreiben')]
    #[Event\ReturnValue(className: StringParameter::class, title: 'Power LED', key: AbstractHcSlave::POWER_LED_KEY)]
    #[Event\ReturnValue(className: StringParameter::class, title: 'Error LED', key: AbstractHcSlave::ERROR_LED_KEY)]
    #[Event\ReturnValue(className: StringParameter::class, title: 'Connect LED', key: AbstractHcSlave::CONNECT_LED_KEY)]
    #[Event\ReturnValue(className: StringParameter::class, title: 'Transceive LED', key: AbstractHcSlave::TRANSCEIVE_LED_KEY)]
    #[Event\ReturnValue(className: StringParameter::class, title: 'Receive LED', key: AbstractHcSlave::RECEIVE_LED_KEY)]
    #[Event\ReturnValue(className: StringParameter::class, title: 'Custom LED', key: AbstractHcSlave::CUSTOM_LED_KEY)]
    public function readRgbLed(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): array {
        return $this->slaveService->readRgbLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Alle LEDs schreiben')]
    public function writeAllLeds(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(BoolParameter::class, 'Power LED')] bool $power,
        #[Event\Parameter(BoolParameter::class, 'Error LED')] bool $error,
        #[Event\Parameter(BoolParameter::class, 'Connect LED')] bool $connect,
        #[Event\Parameter(BoolParameter::class, 'Transreceive LED')] bool $tranresceive,
        #[Event\Parameter(BoolParameter::class, 'Transceive LED')] bool $transceive,
        #[Event\Parameter(BoolParameter::class, 'Receive LED')] bool $resceive,
        #[Event\Parameter(BoolParameter::class, 'Custom LED')] bool $custom,
    ): void {
        $this->slaveService->writeAllLeds(
            $slave,
            $power,
            $error,
            $connect,
            $tranresceive,
            $transceive,
            $resceive,
            $custom
        );
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Alle LEDs lesen')]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Power LED', key: AbstractHcSlave::POWER_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Error LED', key: AbstractHcSlave::ERROR_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Connect LED', key: AbstractHcSlave::CONNECT_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Transreceive LED', key: AbstractHcSlave::TRANSRECEIVE_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Transceive LED', key: AbstractHcSlave::TRANSCEIVE_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Receive LED', key: AbstractHcSlave::RECEIVE_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Custom LED', key: AbstractHcSlave::CUSTOM_LED_KEY)]
    public function readAllLeds(
        #[Event\Parameter(SlaveParameter::class)] Module $slave
    ): array {
        return $this->slaveService->readAllLeds($slave);
    }
}
