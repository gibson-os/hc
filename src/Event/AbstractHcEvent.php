<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Attribute\Event;
use GibsonOS\Core\Dto\Fcm\Message;
use GibsonOS\Core\Dto\Parameter\BoolParameter;
use GibsonOS\Core\Dto\Parameter\EnumParameter;
use GibsonOS\Core\Dto\Parameter\IntParameter;
use GibsonOS\Core\Dto\Parameter\StringParameter;
use GibsonOS\Core\Dto\Parameter\UserParameter;
use GibsonOS\Core\Enum\Middleware\Message\Vibrate;
use GibsonOS\Core\Event\AbstractEvent;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\EventException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\MiddlewareException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Exception\WebException;
use GibsonOS\Core\Manager\ReflectionManager;
use GibsonOS\Core\Model\User;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Core\Service\FcmService;
use GibsonOS\Module\Hc\Dto\Parameter\ModuleParameter;
use GibsonOS\Module\Hc\Dto\Parameter\TypeParameter;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Module\AbstractHcModule;
use JsonException;
use Psr\Log\LoggerInterface;
use ReflectionException;

abstract class AbstractHcEvent extends AbstractEvent
{
    #[Event\Trigger('Vor setzen der Adresse', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'newAddress', 'className' => IntParameter::class, 'title' => 'Neue Adresse'],
    ])]
    public const BEFORE_WRITE_ADDRESS = 'beforeWriteAddress';

    #[Event\Trigger('Nach setzen der Adresse', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'newAddress', 'className' => IntParameter::class, 'title' => 'Neue Adresse'],
    ])]
    public const AFTER_WRITE_ADDRESS = 'afterWriteAddress';

    #[Event\Trigger('Device ID gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'deviceId', 'className' => IntParameter::class, 'title' => 'Device ID'],
    ])]
    public const READ_DEVICE_ID = 'readDeviceId';

    #[Event\Trigger('Vor setzen der Device ID', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'deviceId', 'className' => IntParameter::class, 'title' => 'Device ID'],
    ])]
    public const BEFORE_WRITE_DEVICE_ID = 'beforeWriteDeviceId';

    #[Event\Trigger('Nach setzen der Device ID', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'deviceId', 'className' => IntParameter::class, 'title' => 'Device ID'],
    ])]
    public const AFTER_WRITE_DEVICE_ID = 'afterWriteDeviceId';

    #[Event\Trigger('Typ gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'typeId', 'className' => TypeParameter::class],
    ])]
    public const READ_TYPE_ID = 'readTypeId';

    #[Event\Trigger('Vor setzen des Types', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'typeId', 'className' => TypeParameter::class],
    ])]
    public const BEFORE_WRITE_TYPE_ID = 'beforeWriteTypeId';

    #[Event\Trigger('Nach setzen des Types', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'typeId', 'className' => TypeParameter::class],
    ])]
    public const AFTER_WRITE_TYPE_ID = 'afterWriteTypeId';

    #[Event\Trigger('Vor Neustart', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
    ])]
    public const BEFORE_WRITE_RESTART = 'beforeWriteRestart';

    #[Event\Trigger('Nach Neustart', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
    ])]
    public const AFTER_WRITE_RESTART = 'afterWriteRestart';

    #[Event\Trigger('Konfiguration gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'config', 'className' => StringParameter::class, 'title' => 'Konfiguration'],
    ])]
    public const READ_CONFIG = 'readConfig';

    #[Event\Trigger('Hertz gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'hertz', 'className' => IntParameter::class, 'title' => 'Hertz'],
    ])]
    public const READ_HERTZ = 'readHertz';

    #[Event\Trigger('PWM Speed gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'speed', 'className' => IntParameter::class, 'title' => 'PWM Hertz'],
    ])]
    public const READ_PWM_SPEED = 'readPwmSpeed';

    #[Event\Trigger('Vor PWM Speed setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'speed', 'className' => IntParameter::class, 'title' => 'PWM Hertz'],
    ])]
    public const BEFORE_WRITE_PWM_SPEED = 'beforeWritePwmSpeed';

    #[Event\Trigger('Nach PWM Speed setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'speed', 'className' => IntParameter::class, 'title' => 'PWM Hertz'],
    ])]
    public const AFTER_WRITE_PWM_SPEED = 'afterWritePwmSpeed';

    #[Event\Trigger('EEPROM Größe gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'eepromSize', 'className' => IntParameter::class, 'title' => 'EEPROM Größe'],
    ])]
    public const READ_EEPROM_SIZE = 'readEepromSize';

    #[Event\Trigger('Freier EEPROM Platz gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'eepromFree', 'className' => IntParameter::class, 'title' => 'Freier EEPROM'],
    ])]
    public const READ_EEPROM_FREE = 'readEepromFree';

    #[Event\Trigger('EEPROM Position gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'eepromPosition', 'className' => IntParameter::class, 'title' => 'EEPROM Position'],
    ])]
    public const READ_EEPROM_POSITION = 'readEepromPosition';

    #[Event\Trigger('Vor EEPROM Position setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'eepromPosition', 'className' => IntParameter::class, 'title' => 'EEPROM Position'],
    ])]
    public const BEFORE_WRITE_EEPROM_POSITION = 'beforeWriteEepromPosition';

    #[Event\Trigger('Nach EEPROM Position setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'eepromPosition', 'className' => IntParameter::class, 'title' => 'EEPROM Position'],
    ])]
    public const AFTER_WRITE_EEPROM_POSITION = 'afterWriteEepromPosition';

    #[Event\Trigger('Vor EEPROM formatieren', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
    ])]
    public const BEFORE_WRITE_EEPROM_ERASE = 'beforeWriteEepromErase';

    #[Event\Trigger('Nach EEPROM formatieren', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
    ])]
    public const AFTER_WRITE_EEPROM_ERASE = 'afterWriteEepromErase';

    #[Event\Trigger('Buffer Größe gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'bufferSize', 'className' => IntParameter::class, 'title' => 'Buffer Größe'],
    ])]
    public const READ_BUFFER_SIZE = 'readBufferSize';

    #[Event\Trigger('LED Status gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => AbstractHcModule::POWER_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Power LED'],
        ['key' => AbstractHcModule::ERROR_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Error LED'],
        ['key' => AbstractHcModule::CONNECT_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Connect LED'],
        ['key' => AbstractHcModule::TRANSRECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transreceive LED'],
        ['key' => AbstractHcModule::TRANSCEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transceive LED'],
        ['key' => AbstractHcModule::RECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Receive LED'],
        ['key' => AbstractHcModule::CUSTOM_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Custom LED'],
        ['key' => AbstractHcModule::RGB_LED_KEY, 'className' => BoolParameter::class, 'title' => 'RGB LED'],
    ])]
    public const READ_LED_STATUS = 'readLedStatus';

    #[Event\Trigger('Vor Power LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_POWER_LED = 'beforeWritePowerLed';

    #[Event\Trigger('Nach Power LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_POWER_LED = 'afterWritePowerLed';

    #[Event\Trigger('Vor Error LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_ERROR_LED = 'beforeWriteErrorLed';

    #[Event\Trigger('Nach Error LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_ERROR_LED = 'afterWriteErrorLed';

    #[Event\Trigger('Vor Connect LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_CONNECT_LED = 'beforeWriteConnectLed';

    #[Event\Trigger('Nach Connect LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_CONNECT_LED = 'afterWriteConnectLed';

    #[Event\Trigger('Vor Transreceive LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_TRANSRECEIVE_LED = 'beforeWriteTransreceiveLed';

    #[Event\Trigger('Nach Transreceive LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_TRANSRECEIVE_LED = 'afterWriteTransreceiveLed';

    #[Event\Trigger('Vor Transceive LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_TRANSCEIVE_LED = 'beforeWriteTransceiveLed';

    #[Event\Trigger('Nach Transceive LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_TRANSCEIVE_LED = 'afterWriteTransceiveLed';

    #[Event\Trigger('Vor Receive LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_RECEIVE_LED = 'beforeWriteReceiveLed';

    #[Event\Trigger('nach Receive LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_RECEIVE_LED = 'afterWriteReceiveLed';

    #[Event\Trigger('Vor Custom LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const BEFORE_WRITE_CUSTOM_LED = 'beforeWriteCustomLed';

    #[Event\Trigger('Nach Custom LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const AFTER_WRITE_CUSTOM_LED = 'afterWriteCustomLed';

    #[Event\Trigger('Power LED gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_POWER_LED = 'readPowerLed';

    #[Event\Trigger('Error LED gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_ERROR_LED = 'readErrorLed';

    #[Event\Trigger('Connect LED gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_CONNECT_LED = 'readConnectLed';

    #[Event\Trigger('Transreceive LED gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_TRANSRECEIVE_LED = 'readTransreceiveLed';

    #[Event\Trigger('Transceive LED gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_TRANSCEIVE_LED = 'readTransceiveLed';

    #[Event\Trigger('Receive LED gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_RECEIVE_LED = 'readReceiveLed';

    #[Event\Trigger('Custom LED gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'on', 'className' => BoolParameter::class, 'title' => 'An'],
    ])]
    public const READ_CUSTOM_LED = 'readCustomLed';

    #[Event\Trigger('Vor RGB LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => AbstractHcModule::POWER_LED_KEY, 'className' => StringParameter::class, 'Power LED'],
        ['key' => AbstractHcModule::ERROR_LED_KEY, 'className' => StringParameter::class, 'Error LED'],
        ['key' => AbstractHcModule::CONNECT_LED_KEY, 'className' => StringParameter::class, 'Connect LED'],
        ['key' => AbstractHcModule::TRANSCEIVE_LED_KEY, 'className' => StringParameter::class, 'Transceive LED'],
        ['key' => AbstractHcModule::RECEIVE_LED_KEY, 'className' => StringParameter::class, 'Receive LED'],
        ['key' => AbstractHcModule::CUSTOM_LED_KEY, 'className' => StringParameter::class, 'Custom LED'],
    ])]
    public const BEFORE_WRITE_RGB_LED = 'beforeWriteRgbLed';

    #[Event\Trigger('Nach RGB LED setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => AbstractHcModule::POWER_LED_KEY, 'className' => StringParameter::class, 'Power LED'],
        ['key' => AbstractHcModule::ERROR_LED_KEY, 'className' => StringParameter::class, 'Error LED'],
        ['key' => AbstractHcModule::CONNECT_LED_KEY, 'className' => StringParameter::class, 'Connect LED'],
        ['key' => AbstractHcModule::TRANSCEIVE_LED_KEY, 'className' => StringParameter::class, 'Transceive LED'],
        ['key' => AbstractHcModule::RECEIVE_LED_KEY, 'className' => StringParameter::class, 'Receive LED'],
        ['key' => AbstractHcModule::CUSTOM_LED_KEY, 'className' => StringParameter::class, 'Custom LED'],
    ])]
    public const AFTER_WRITE_RGB_LED = 'afterWriteRgbLed';

    #[Event\Trigger('RGB LED gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => AbstractHcModule::POWER_LED_KEY, 'className' => StringParameter::class, 'Power LED'],
        ['key' => AbstractHcModule::ERROR_LED_KEY, 'className' => StringParameter::class, 'Error LED'],
        ['key' => AbstractHcModule::CONNECT_LED_KEY, 'className' => StringParameter::class, 'Connect LED'],
        ['key' => AbstractHcModule::TRANSCEIVE_LED_KEY, 'className' => StringParameter::class, 'Transceive LED'],
        ['key' => AbstractHcModule::RECEIVE_LED_KEY, 'className' => StringParameter::class, 'Receive LED'],
        ['key' => AbstractHcModule::CUSTOM_LED_KEY, 'className' => StringParameter::class, 'Custom LED'],
    ])]
    public const READ_RGB_LED = 'readRgbLed';

    #[Event\Trigger('Alle LEDs gelesen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => AbstractHcModule::POWER_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Power LED'],
        ['key' => AbstractHcModule::ERROR_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Error LED'],
        ['key' => AbstractHcModule::CONNECT_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Connect LED'],
        ['key' => AbstractHcModule::TRANSRECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transreceive LED'],
        ['key' => AbstractHcModule::TRANSCEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transceive LED'],
        ['key' => AbstractHcModule::RECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Receive LED'],
        ['key' => AbstractHcModule::CUSTOM_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Custom LED'],
    ])]
    public const READ_ALL_LEDS = 'readAllLeds';

    #[Event\Trigger('Vor alle LEDs setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => AbstractHcModule::POWER_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Power LED'],
        ['key' => AbstractHcModule::ERROR_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Error LED'],
        ['key' => AbstractHcModule::CONNECT_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Connect LED'],
        ['key' => AbstractHcModule::TRANSRECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transreceive LED'],
        ['key' => AbstractHcModule::TRANSCEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transceive LED'],
        ['key' => AbstractHcModule::RECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Receive LED'],
        ['key' => AbstractHcModule::CUSTOM_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Custom LED'],
    ])]
    public const BEFORE_WRITE_ALL_LEDS = 'beforeWriteAllLeds';

    #[Event\Trigger('Nach alle LEDs setzen', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => AbstractHcModule::POWER_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Power LED'],
        ['key' => AbstractHcModule::ERROR_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Error LED'],
        ['key' => AbstractHcModule::CONNECT_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Connect LED'],
        ['key' => AbstractHcModule::TRANSRECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transreceive LED'],
        ['key' => AbstractHcModule::TRANSCEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Transceive LED'],
        ['key' => AbstractHcModule::RECEIVE_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Receive LED'],
        ['key' => AbstractHcModule::CUSTOM_LED_KEY, 'className' => BoolParameter::class, 'title' => 'Custom LED'],
        ['key' => AbstractHcModule::RGB_LED_KEY, 'className' => BoolParameter::class, 'title' => 'RGB LED'],
    ])]
    public const AFTER_WRITE_ALL_LEDS = 'afterWriteAllLeds';

    public function __construct(
        EventService $eventService,
        ReflectionManager $reflectionManager,
        private readonly TypeRepository $typeRepository,
        protected readonly LoggerInterface $logger,
        private readonly FcmService $fcmService,
        private readonly AbstractHcModule $moduleService,
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
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(IntParameter::class, 'Adresse')]
        int $address,
    ): void {
        $this->moduleService->writeAddress($slave, $address);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     * @throws GetError
     */
    #[Event\Method('Adresse lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Adresse')]
    public function readDeviceId(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): int {
        return $this->moduleService->readDeviceId($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('Device ID schreiben')]
    public function writeDeviceId(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(IntParameter::class, 'Device ID')]
        int $deviceId,
    ): void {
        $this->moduleService->writeDeviceId($slave, $deviceId);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Typ lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Typ')]
    public function readTypeId(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): int {
        return $this->moduleService->readTypeId($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[Event\Method('Typ schreiben')]
    public function writeTypeId(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(IntParameter::class, 'Typ')]
        int $typeId,
    ): void {
        $type = $this->typeRepository->getById($typeId);
        $this->moduleService->writeTypeId($slave, $type);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('Neustarten')]
    public function writeRestart(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): void {
        $this->moduleService->writeRestart($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Hertz lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Hertz')]
    public function readHertz(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): int {
        return $this->moduleService->readHertz($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('EEPROM Größe lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Größe')]
    public function readEepromSize(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): int {
        return $this->moduleService->readEepromSize($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Freier Platz im EEPROM lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Freier Platz')]
    public function readEepromFree(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): int {
        return $this->moduleService->readEepromFree($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('EEPROM Zeigerposition lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Zeigerposition')]
    public function readEepromPosition(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): int {
        return $this->moduleService->readEepromPosition($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('EEPROM Zeigerposition schreiben')]
    public function writeEepromPosition(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(IntParameter::class, 'Position')]
        int $position,
    ): void {
        $this->moduleService->writeEepromPosition($slave, $position);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('Eeprom formatieren')]
    public function writeEepromErase(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): void {
        $this->moduleService->writeEepromErase($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Buffer Größe lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Größe')]
    public function readBufferSize(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): int {
        return $this->moduleService->readBufferSize($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('PWM Geschwindigkeit lesen')]
    #[Event\ReturnValue(IntParameter::class, 'Geschwindigkeit')]
    public function readPwmSpeed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): int {
        return $this->moduleService->readPwmSpeed($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('PWM Geschwindigkeit lesen')]
    public function writePwmSpeed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(IntParameter::class, 'Geschwindigkeit')]
        int $pwmSpeed,
    ): void {
        $this->moduleService->writePwmSpeed($slave, $pwmSpeed);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('LED Status lesen')]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Power LED', key: AbstractHcModule::POWER_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Error LED', key: AbstractHcModule::ERROR_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Connect LED', key: AbstractHcModule::CONNECT_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Transreceive LED', key: AbstractHcModule::TRANSRECEIVE_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Transceive LED', key: AbstractHcModule::TRANSCEIVE_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Receive LED', key: AbstractHcModule::RECEIVE_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Custom LED', key: AbstractHcModule::CUSTOM_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'RGB LED', key: AbstractHcModule::RGB_LED_KEY)]
    public function readLedStatus(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): array {
        return $this->moduleService->readLedStatus($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('Power LED schreiben')]
    public function writePowerLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')]
        bool $on,
    ): void {
        $this->moduleService->writePowerLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('Error LED schreiben')]
    public function writeErrorLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')]
        bool $on,
    ): void {
        $this->moduleService->writeErrorLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('Connect LED schreiben')]
    public function writeConnectLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')]
        bool $on,
    ): void {
        $this->moduleService->writeConnectLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('Tranreceive LED schreiben')]
    public function writeTransreceiveLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')]
        bool $on,
    ): void {
        $this->moduleService->writeTransreceiveLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('Transceive LED schreiben')]
    public function writeTransceiveLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')]
        bool $on,
    ): void {
        $this->moduleService->writeTransceiveLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('Receive LED schreiben')]
    public function writeReceiveLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')]
        bool $on,
    ): void {
        $this->moduleService->writeReceiveLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('Custom LED schreiben')]
    public function writeCustomLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(BoolParameter::class, 'An')]
        bool $on,
    ): void {
        $this->moduleService->writeCustomLed($slave, $on);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Power LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readPowerLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): bool {
        return $this->moduleService->readPowerLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Error LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readErrorLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): bool {
        return $this->moduleService->readErrorLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Connect LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readConnectLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): bool {
        return $this->moduleService->readConnectLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Transreceive LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readTransreceiveLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): bool {
        return $this->moduleService->readTransreceiveLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Transceive LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readTransceiveLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): bool {
        return $this->moduleService->readTransceiveLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Receive LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readReceiveLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): bool {
        return $this->moduleService->readReceiveLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Custom LED lesen')]
    #[Event\ReturnValue(BoolParameter::class, 'An')]
    public function readCustomLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): bool {
        return $this->moduleService->readCustomLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('RGB LED schreiben')]
    public function writeRgbLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(StringParameter::class, 'Power LED')]
        string $power,
        #[Event\Parameter(StringParameter::class, 'Error LED')]
        string $error,
        #[Event\Parameter(StringParameter::class, 'Connect LED')]
        string $connect,
        #[Event\Parameter(StringParameter::class, 'Transceive LED')]
        string $transceive,
        #[Event\Parameter(StringParameter::class, 'Receive LED')]
        string $resceive,
        #[Event\Parameter(StringParameter::class, 'Custom LED')]
        string $custom,
    ): void {
        $this->moduleService->writeRgbLed(
            $slave,
            $power,
            $error,
            $connect,
            $transceive,
            $resceive,
            $custom,
        );
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('RGB LED schreiben')]
    #[Event\ReturnValue(className: StringParameter::class, title: 'Power LED', key: AbstractHcModule::POWER_LED_KEY)]
    #[Event\ReturnValue(className: StringParameter::class, title: 'Error LED', key: AbstractHcModule::ERROR_LED_KEY)]
    #[Event\ReturnValue(className: StringParameter::class, title: 'Connect LED', key: AbstractHcModule::CONNECT_LED_KEY)]
    #[Event\ReturnValue(className: StringParameter::class, title: 'Transceive LED', key: AbstractHcModule::TRANSCEIVE_LED_KEY)]
    #[Event\ReturnValue(className: StringParameter::class, title: 'Receive LED', key: AbstractHcModule::RECEIVE_LED_KEY)]
    #[Event\ReturnValue(className: StringParameter::class, title: 'Custom LED', key: AbstractHcModule::CUSTOM_LED_KEY)]
    public function readRgbLed(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): array {
        return $this->moduleService->readRgbLed($slave);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('Alle LEDs schreiben')]
    public function writeAllLeds(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(BoolParameter::class, 'Power LED')]
        bool $power,
        #[Event\Parameter(BoolParameter::class, 'Error LED')]
        bool $error,
        #[Event\Parameter(BoolParameter::class, 'Connect LED')]
        bool $connect,
        #[Event\Parameter(BoolParameter::class, 'Transreceive LED')]
        bool $tranresceive,
        #[Event\Parameter(BoolParameter::class, 'Transceive LED')]
        bool $transceive,
        #[Event\Parameter(BoolParameter::class, 'Receive LED')]
        bool $resceive,
        #[Event\Parameter(BoolParameter::class, 'Custom LED')]
        bool $custom,
    ): void {
        $this->moduleService->writeAllLeds(
            $slave,
            $power,
            $error,
            $connect,
            $tranresceive,
            $transceive,
            $resceive,
            $custom,
        );
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Alle LEDs lesen')]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Power LED', key: AbstractHcModule::POWER_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Error LED', key: AbstractHcModule::ERROR_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Connect LED', key: AbstractHcModule::CONNECT_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Transreceive LED', key: AbstractHcModule::TRANSRECEIVE_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Transceive LED', key: AbstractHcModule::TRANSCEIVE_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Receive LED', key: AbstractHcModule::RECEIVE_LED_KEY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Custom LED', key: AbstractHcModule::CUSTOM_LED_KEY)]
    public function readAllLeds(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
    ): array {
        return $this->moduleService->readAllLeds($slave);
    }

    /**
     * @throws JsonException
     * @throws SaveError
     * @throws WebException
     * @throws MiddlewareException
     */
    #[Event\Method('Nachricht senden')]
    public function pushMessage(
        #[Event\Parameter(UserParameter::class)]
        User $user,
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(StringParameter::class, 'Titel')]
        ?string $title,
        #[Event\Parameter(StringParameter::class, 'Text')]
        ?string $body,
        #[Event\Parameter(EnumParameter::class, 'Vibration', ['className' => [Vibrate::class]])]
        ?Vibrate $vibrate,
    ): void {
        foreach ($user->getDevices() as $device) {
            $token = $device->getToken();
            $fcmToken = $device->getFcmToken();

            if ($token === null || $fcmToken === null) {
                continue;
            }

            $this->fcmService->pushMessage(new Message(
                $token,
                $fcmToken,
                title: $title,
                body: $body,
                module: 'hc',
                task: $module->getType()->getHelper(),
                action: 'index',
                data: ['module' => $module],
                vibrate: $vibrate,
            ));
        }
    }
}
