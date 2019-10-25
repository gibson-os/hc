<?php
namespace GibsonOS\Module\Hc\Service\Event\Describer;

use GibsonOS\Module\Hc\Config\Event\Describer\Parameter\BoolParameter;
use GibsonOS\Module\Hc\Config\Event\Describer\Parameter\IntParameter;
use GibsonOS\Module\Hc\Config\Event\Describer\Parameter\SlaveParameter;
use GibsonOS\Module\Hc\Config\Event\Describer\Parameter\StringParameter;
use GibsonOS\Module\Hc\Config\Event\Describer\Parameter\TypeParameter;
use GibsonOS\Module\Hc\Config\Event\Describer\Trigger;
use GibsonOS\Module\Hc\Config\Event\Describer\Method;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;

class Hc implements DescriberInterface
{
    public const BEFORE_WRITE_ADDRESS = 'beforeWriteAddress';
    public const AFTER_WRITE_ADDRESS = 'afterWriteAddress';
    public const READ_DEVICE_ID = 'readDeviceId';
    public const BEFORE_WRITE_DEVICE_ID = 'beforeWriteDeviceId';
    public const AFTER_WRITE_DEVICE_ID = 'afterWriteDeviceId';
    public const READ_TYPE = 'readType';
    public const BEFORE_WRITE_TYPE = 'beforeWriteType';
    public const AFTER_WRITE_TYPE = 'afterWriteType';
    public const BEFORE_WRITE_RESTART = 'beforeWriteRestart';
    public const AFTER_WRITE_RESTART = 'afterWriteRestart';
    public const READ_CONFIG = 'readConfig';
    public const READ_HERTZ = 'readHertz';
    public const READ_PWM_SPEED = 'readPwmSpeed';
    public const BEFORE_WRITE_PWM_SPEED = 'beforeWritePwmSpeed';
    public const AFTER_WRITE_PWM_SPEED = 'afterWritePwmSpeed';
    public const READ_EEPROM_SIZE = 'readEepromSize';
    public const READ_EEPROM_FREE = 'readEepromFree';
    public const READ_EEPROM_POSITION = 'readEepromPosition';
    public const BEFORE_WRITE_EEPROM_POSITION = 'beforeWriteEepromPosition';
    public const AFTER_WRITE_EEPROM_POSITION = 'afterWriteEepromPosition';
    public const BEFORE_WRITE_EEPROM_ERASE = 'beforeWriteEepromErase';
    public const AFTER_WRITE_EEPROM_ERASE = 'afterWriteEepromErase';
    public const READ_BUFFER_SIZE = 'readBufferSize';
    public const READ_LED_STATUS = 'readLedStatus';
    public const BEFORE_WRITE_POWER_LED = 'beforeWritePowerLed';
    public const AFTER_WRITE_POWER_LED = 'afterWritePowerLed';
    public const BEFORE_WRITE_ERROR_LED = 'beforeWriteErrorLed';
    public const AFTER_WRITE_ERROR_LED = 'afterWriteErrorLed';
    public const BEFORE_WRITE_CONNECT_LED = 'beforeWriteConnectLed';
    public const AFTER_WRITE_CONNECT_LED = 'afterWriteConnectLed';
    public const BEFORE_WRITE_TRANSRECEIVE_LED = 'beforeWriteTransreceiveLed';
    public const AFTER_WRITE_TRANSRECEIVE_LED = 'afterWriteTransreceiveLed';
    public const BEFORE_WRITE_TRANSCEIVE_LED = 'beforeWriteTransceiveLed';
    public const AFTER_WRITE_TRANSCEIVE_LED = 'afterWriteTransceiveLed';
    public const BEFORE_WRITE_RECEIVE_LED = 'beforeWriteReceiveLed';
    public const AFTER_WRITE_RECEIVE_LED = 'afterWriteReceiveLed';
    public const BEFORE_WRITE_CUSTOM_LED = 'beforeWriteCustomLed';
    public const AFTER_WRITE_CUSTOM_LED = 'afterWriteCustomLed';
    public const READ_POWER_LED = 'readPowerLed';
    public const READ_ERROR_LED = 'readErrorLed';
    public const READ_CONNECT_LED = 'readConnectLed';
    public const READ_TRANSRECEIVE_LED = 'readTransreceiveLed';
    public const READ_TRANSCEIVE_LED = 'readTransceiveLed';
    public const READ_RECEIVE_LED = 'readReceiveLed';
    public const READ_CUSTOM_LED = 'readCustomLed';
    public const BEFORE_WRITE_RGB_LED = 'beforeWriteRgbLed';
    public const AFTER_WRITE_RGB_LED = 'afterWriteRgbLed';
    public const READ_RGB_LED = 'readRgbLed';
    public const READ_ALL_LEDS = 'readAllLeds';
    public const BEFORE_WRITE_ALL_LEDS = 'beforeWriteAllLeds';
    public const AFTER_WRITE_ALL_LEDS = 'afterWriteAllLeds';

    /**
     * @var SlaveParameter
     */
    private $slaveParameter;

    public function __construct()
    {
        $this->slaveParameter = new SlaveParameter();
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return 'HC Sklave';
    }

    /**
     * @return Trigger[]
     */
    public function getTriggers(): array
    {
        $ledOnParameters = [
            'slave' => $this->slaveParameter,
            'on' => new BoolParameter('An')
        ];
        $ledsParameters = [
            'slave' => $this->slaveParameter,
            AbstractHcSlave::POWER_LED_KEY => new StringParameter('Power LED'),
            AbstractHcSlave::ERROR_LED_KEY => new StringParameter('Error LED'),
            AbstractHcSlave::CONNECT_LED_KEY => new StringParameter('Connect LED'),
            AbstractHcSlave::TRANSCEIVE_LED_KEY => new StringParameter('Transceive LED'),
            AbstractHcSlave::RECEIVE_LED_KEY => new StringParameter('Receive LED'),
            AbstractHcSlave::CUSTOM_LED_KEY => new StringParameter('Custom LED')
        ];
        $ledsBoolParameters = [
            'slave' => $this->slaveParameter,
            AbstractHcSlave::POWER_LED_KEY => new BoolParameter('Power LED'),
            AbstractHcSlave::ERROR_LED_KEY => new BoolParameter('Error LED'),
            AbstractHcSlave::CONNECT_LED_KEY => new BoolParameter('Connect LED'),
            AbstractHcSlave::TRANSRECEIVE_LED_KEY => new BoolParameter('Transreceive LED'),
            AbstractHcSlave::TRANSCEIVE_LED_KEY => new BoolParameter('Transceive LED'),
            AbstractHcSlave::RECEIVE_LED_KEY => new BoolParameter('Receive LED'),
            AbstractHcSlave::CUSTOM_LED_KEY => new BoolParameter('Custom LED')
        ];

        return [
            self::BEFORE_WRITE_ADDRESS => (new Trigger('Vor setzen der Adresse'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'newAddress' => new IntParameter('Neue Adresse')
                ]),
            self::AFTER_WRITE_ADDRESS => (new Trigger('Nach setzen der Adresse'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'newAddress' => new IntParameter('Neue Adresse')
                ]),
            self::READ_DEVICE_ID => (new Trigger('Device ID gelesen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'deviceId' => new IntParameter('Device ID')
                ]),
            self::BEFORE_WRITE_DEVICE_ID => (new Trigger('Vor setzen der Device ID'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'deviceId' => new IntParameter('Device ID')
                ]),
            self::AFTER_WRITE_DEVICE_ID => (new Trigger('Nach setzen der Device ID'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'newDeviceId' => new IntParameter('Neue Device ID')
                ]),
            self::READ_TYPE => (new Trigger('Typ gelesen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'typeId' => new TypeParameter()
                ]),
            self::BEFORE_WRITE_TYPE => (new Trigger('Vor setzen des Types'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'typeId' => new TypeParameter()
                ]),
            self::AFTER_WRITE_TYPE => (new Trigger('Nach setzen des Types'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'typeId' => new TypeParameter()
                ]),
            self::BEFORE_WRITE_RESTART => (new Trigger('Vor Neustart'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::AFTER_WRITE_RESTART => (new Trigger('Nach Neustart'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::READ_CONFIG => (new Trigger('Konfiguration gelesen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'config' => new StringParameter('Konfiguration')
                ]),
            self::READ_HERTZ => (new Trigger('Hertz gelesen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'hertz' => new IntParameter('Hertz')
                ]),
            self::READ_PWM_SPEED => (new Trigger('PWM Speed gelesen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'speed' => new IntParameter('Hertz')
                ]),
            self::BEFORE_WRITE_PWM_SPEED => (new Trigger('Vor PWM Speed setzen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'speed' => new IntParameter('PWM Hertz')
                ]),
            self::AFTER_WRITE_PWM_SPEED => (new Trigger('Nach PWM Speed setzen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'speed' => new IntParameter('PWM Hertz')
                ]),
            self::READ_EEPROM_SIZE => (new Trigger('EEPROM Größe gelesen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'eepromSize' => new IntParameter('EEPROM Größe')
                ]),
            self::READ_EEPROM_FREE => (new Trigger('Freier EEPROM Platz gelesen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'eepromFree' => new IntParameter('Freier EEPROM')
                ]),
            self::READ_EEPROM_POSITION => (new Trigger('EEPROM Position gelesen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'eepromPosition' => new IntParameter('EEPROM Position')
                ]),
            self::BEFORE_WRITE_EEPROM_POSITION => (new Trigger('Vor EEPROM Position setzen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'eepromPosition' => new IntParameter('EEPROM Position')
                ]),
            self::AFTER_WRITE_EEPROM_POSITION => (new Trigger('Nach EEPROM Position setzen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'eepromPosition' => new IntParameter('EEPROM Position')
                ]),
            self::BEFORE_WRITE_EEPROM_ERASE => (new Trigger('Vor EEPROM formatieren'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::AFTER_WRITE_EEPROM_ERASE => (new Trigger('Nach EEPROM formatieren'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::READ_BUFFER_SIZE => (new Trigger('Buffer Größe gelesen'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::READ_LED_STATUS => (new Trigger('LED Status gelesen'))
                ->setParameters(array_merge($ledsBoolParameters, [
                    AbstractHcSlave::RGB_LED_KEY => new BoolParameter('RGB LED')
                ])),
            self::BEFORE_WRITE_POWER_LED => (new Trigger('Vor Power LED setzen'))
                ->setParameters($ledOnParameters),
            self::AFTER_WRITE_POWER_LED => (new Trigger('Nach Power LED setzen'))
                ->setParameters($ledOnParameters),
            self::BEFORE_WRITE_ERROR_LED => (new Trigger('Vor Error LED setzen'))
                ->setParameters($ledOnParameters),
            self::AFTER_WRITE_ERROR_LED => (new Trigger('Nach Error LED setzen'))
                ->setParameters($ledOnParameters),
            self::BEFORE_WRITE_CONNECT_LED => (new Trigger('Vor Connect LED setzen'))
                ->setParameters($ledOnParameters),
            self::AFTER_WRITE_CONNECT_LED => (new Trigger('Nach Power LED setzen'))
                ->setParameters($ledOnParameters),
            self::BEFORE_WRITE_TRANSRECEIVE_LED => (new Trigger('Vor Transreceive LED setzen'))
                ->setParameters($ledOnParameters),
            self::AFTER_WRITE_TRANSRECEIVE_LED => (new Trigger('Nach Transreceive LED setzen'))
                ->setParameters($ledOnParameters),
            self::BEFORE_WRITE_TRANSCEIVE_LED => (new Trigger('Vor Transceive LED setzen'))
                ->setParameters($ledOnParameters),
            self::AFTER_WRITE_TRANSCEIVE_LED => (new Trigger('Nach Transceive LED setzen'))
                ->setParameters($ledOnParameters),
            self::BEFORE_WRITE_RECEIVE_LED => (new Trigger('Vor Receive LED setzen'))
                ->setParameters($ledOnParameters),
            self::AFTER_WRITE_RECEIVE_LED => (new Trigger('Nach Receive LED setzen'))
                ->setParameters($ledOnParameters),
            self::BEFORE_WRITE_CUSTOM_LED => (new Trigger('Vor Custom LED setzen'))
                ->setParameters($ledOnParameters),
            self::AFTER_WRITE_CUSTOM_LED => (new Trigger('Nach Custom LED setzen'))
                ->setParameters($ledOnParameters),
            self::READ_POWER_LED => (new Trigger('Connect LED gelesen'))
                ->setParameters($ledOnParameters),
            self::READ_ERROR_LED => (new Trigger('Error LED gelesen'))
                ->setParameters($ledOnParameters),
            self::READ_CONNECT_LED => (new Trigger('Connect LED gelesen'))
                ->setParameters($ledOnParameters),
            self::READ_TRANSRECEIVE_LED => (new Trigger('Transreceive LED gelesen'))
                ->setParameters($ledOnParameters),
            self::READ_TRANSCEIVE_LED => (new Trigger('Transceive LED gelesen'))
                ->setParameters($ledOnParameters),
            self::READ_RECEIVE_LED => (new Trigger('Receive LED gelesen'))
                ->setParameters($ledOnParameters),
            self::READ_CUSTOM_LED => (new Trigger('Custom LED gelesen'))
                ->setParameters($ledOnParameters),
            self::BEFORE_WRITE_RGB_LED => (new Trigger('Custom LED gelesen'))
                ->setParameters($ledsParameters),
            self::AFTER_WRITE_RGB_LED => (new Trigger('Vor RGB LED setzen'))
                ->setParameters($ledsParameters),
            self::READ_RGB_LED => (new Trigger('Nach RGB LED setzen'))
                ->setParameters($ledsParameters),
            self::READ_ALL_LEDS => (new Trigger('Alle LEDs gelesen'))
                ->setParameters($ledsBoolParameters),
            self::BEFORE_WRITE_ALL_LEDS => (new Trigger('Vor alle LEDs setzen'))
                ->setParameters($ledsBoolParameters),
            self::AFTER_WRITE_ALL_LEDS => (new Trigger('Nach alle LEDs setzen'))
                ->setParameters($ledsBoolParameters)
        ];
    }

    /**
     * @return Method[]
     */
    public function getMethods(): array
    {
        $ledParameters = [
            'slave' => $this->slaveParameter,
            'on' => new BoolParameter('An')
        ];

        return [
            'writeAddress' => (new Method('Adresse schreiben'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'address' => new IntParameter('Adresse')
                ]),
            'readDeviceId' => (new Method('Adresse lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new IntParameter('Adresse')]),
            'writeDeviceId' => (new Method('Device ID schreiben'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'deviceId' => new IntParameter('Geräte ID')
                ]),
            'readTypeId' => (new Method('Typ lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new IntParameter('Typ')]),
            'writeTypeId' => (new Method('Typ schreiben'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'typeId' => new IntParameter('Typ')
                ]),
            'writeRestart' => (new Method('Neustarten'))
                ->setParameters(['slave' => $this->slaveParameter]),
            'readHertz' => (new Method('Hertz lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new IntParameter('Hertz')]),
            'readEepromSize' => (new Method('EEPROM Größe lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new IntParameter('Größe')]),
            'readEepromFree' => (new Method('Freier Platz im EEPROM lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new IntParameter('Freier Platz')]),
            'readEepromPosition' => (new Method('EEPROM Zeigerposition lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new IntParameter('Zeigerposition')]),
            'writeEepromPosition' => (new Method('EEPROM Zeigerposition schreiben'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'position' => new IntParameter('Zeigerposition')
                ]),
            'writeEepromErase' => (new Method('Eeprom formatieren'))
                ->setParameters(['slave' => $this->slaveParameter]),
            'readBufferSize' => (new Method('Buffer Größe lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new IntParameter('Größe')]),
            'readLedStatus' => (new Method('LED Status lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([
                    AbstractHcSlave::POWER_LED_KEY => new BoolParameter('Power LED'),
                    AbstractHcSlave::ERROR_LED_KEY => new BoolParameter('Error LED'),
                    AbstractHcSlave::CONNECT_LED_KEY => new BoolParameter('Connect LED'),
                    AbstractHcSlave::TRANSRECEIVE_LED_KEY => new BoolParameter('Transreceive LED'),
                    AbstractHcSlave::TRANSCEIVE_LED_KEY => new BoolParameter('Transceive LED'),
                    AbstractHcSlave::RECEIVE_LED_KEY => new BoolParameter('Receive LED'),
                    AbstractHcSlave::CUSTOM_LED_KEY => new BoolParameter('Custom LED'),
                    AbstractHcSlave::RGB_LED_KEY => new BoolParameter('RGB LED')
                ]),
            'writePowerLed' => (new Method('Power LED schreiben'))
                ->setParameters($ledParameters),
            'writeErrorLed' => (new Method('Error LED schreiben'))
                ->setParameters($ledParameters),
            'writeConnectLed' => (new Method('Connect LED schreiben'))
                ->setParameters($ledParameters),
            'writeTransreceiveLed' => (new Method('Transreceive LED schreiben'))
                ->setParameters($ledParameters),
            'writeTransceiveLed' => (new Method('Transceive LED schreiben'))
                ->setParameters($ledParameters),
            'writeReceiveLed' => (new Method('Receive LED schreiben'))
                ->setParameters($ledParameters),
            'writeCustomLed' => (new Method('Custom LED schreiben'))
                ->setParameters($ledParameters),
            'readPowerLed' => (new Method('Power LED lesen'))
                ->setParameters($ledParameters)
                ->setReturnTypes([new BoolParameter('An')]),
            'readErrorLed' => (new Method('Error LED lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new BoolParameter('An')]),
            'readConnectLed' => (new Method('Connect LED lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new BoolParameter('An')]),
            'readTransrecieveLed' => (new Method('Transreceive LED lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new BoolParameter('An')]),
            'readTransceiveLed' => (new Method('Transceive LED lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new BoolParameter('An')]),
            'readReceiveLed' => (new Method('Receive LED lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new BoolParameter('An')]),
            'readCustomLed' => (new Method('Custom LED lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([new BoolParameter('An')]),
            'writeRgbLed' => (new Method('RGB LED schreiben'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    AbstractHcSlave::POWER_LED_KEY => new StringParameter('Power LED'),
                    AbstractHcSlave::ERROR_LED_KEY => new StringParameter('Error LED'),
                    AbstractHcSlave::CONNECT_LED_KEY => new StringParameter('Connect LED'),
                    AbstractHcSlave::TRANSCEIVE_LED_KEY => new StringParameter('Transceive LED'),
                    AbstractHcSlave::TRANSRECEIVE_LED_KEY => new StringParameter('Transreceive LED'),
                    AbstractHcSlave::RECEIVE_LED_KEY => new StringParameter('Receive LED'),
                    AbstractHcSlave::CUSTOM_LED_KEY => new StringParameter('Custom LED'),
                ]),
            'readRgbLed' => (new Method('RGB LED lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([
                    AbstractHcSlave::POWER_LED_KEY => new StringParameter('Power LED'),
                    AbstractHcSlave::ERROR_LED_KEY => new StringParameter('Error LED'),
                    AbstractHcSlave::CONNECT_LED_KEY => new StringParameter('Connect LED'),
                    AbstractHcSlave::TRANSCEIVE_LED_KEY => new StringParameter('Transceive LED'),
                    AbstractHcSlave::RECEIVE_LED_KEY => new StringParameter('Receive LED'),
                    AbstractHcSlave::CUSTOM_LED_KEY => new StringParameter('Custom LED')
                ]),
            'writeAllLeds' => (new Method('Alle LEDs schreiben'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    AbstractHcSlave::POWER_LED_KEY => new BoolParameter('Power LED'),
                    AbstractHcSlave::ERROR_LED_KEY => new BoolParameter('Error LED'),
                    AbstractHcSlave::CONNECT_LED_KEY => new BoolParameter('Connect LED'),
                    AbstractHcSlave::TRANSCEIVE_LED_KEY => new BoolParameter('Transceive LED'),
                    AbstractHcSlave::TRANSRECEIVE_LED_KEY => new BoolParameter('Transreceive LED'),
                    AbstractHcSlave::RECEIVE_LED_KEY => new BoolParameter('Receive LED'),
                    AbstractHcSlave::CUSTOM_LED_KEY => new BoolParameter('Custom LED')
                ]),
            'readAllLed' => (new Method('Alle LEDs lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturnTypes([
                    AbstractHcSlave::POWER_LED_KEY => new BoolParameter('Power LED'),
                    AbstractHcSlave::ERROR_LED_KEY => new BoolParameter('Error LED'),
                    AbstractHcSlave::CONNECT_LED_KEY => new BoolParameter('Connect LED'),
                    AbstractHcSlave::TRANSRECEIVE_LED_KEY => new BoolParameter('Transreceive LED'),
                    AbstractHcSlave::TRANSCEIVE_LED_KEY => new BoolParameter('Transceive LED'),
                    AbstractHcSlave::RECEIVE_LED_KEY => new BoolParameter('Receive LED'),
                    AbstractHcSlave::CUSTOM_LED_KEY => new BoolParameter('Custom LED')
                ])
        ];
    }
}