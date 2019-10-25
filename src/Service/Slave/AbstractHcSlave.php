<?php
namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Factory\Slave as SlaveFactory;
use GibsonOS\Module\Hc\Model\Log as LogModel;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type as TypeModel;
use GibsonOS\Module\Hc\Repository\Master;
use GibsonOS\Module\Hc\Repository\Module as ModuleRepository;
use GibsonOS\Module\Hc\Service\Event\Describer\Hc;
use GibsonOS\Module\Hc\Service\Master as MasterService;
use GibsonOS\Module\Hc\Service\Server;
use GibsonOS\Module\Hc\Utility\Transform;

abstract class AbstractHcSlave extends AbstractSlave
{
    const TYPE = 0;
    const MAX_DEVICE_ID = 65534;

    const COMMAND_DEVICE_ID = 200;
    const COMMAND_DEVICE_ID_READ_LENGTH = 2;
    const COMMAND_TYPE = 201;
    const COMMAND_TYPE_READ_LENGTH = 1;
    const COMMAND_ADDRESS = 202;
    const COMMAND_RESTART = 209;
    const COMMAND_CONFIGURATION = 210;
    const COMMAND_HERTZ = 211;
    const COMMAND_HERTZ_READ_LENGTH = 4;
    const COMMAND_EEPROM_SIZE = 212;
    const COMMAND_EEPROM_SIZE_READ_LENGTH = 2;
    const COMMAND_EEPROM_FREE = 213;
    const COMMAND_EEPROM_FREE_READ_LENGTH = 2;
    const COMMAND_EEPROM_POSITION = 214;
    const COMMAND_EEPROM_POSITION_READ_LENGTH = 2;
    const COMMAND_EEPROM_ERASE = 215;
    const COMMAND_BUFFER_SIZE = 216;
    const COMMAND_BUFFER_SIZE_READ_LENGTH = 2;
    const COMMAND_PWM_SPEED = 217;
    const COMMAND_PWM_SPEED_READ_LENGTH = 2;
    const COMMAND_LEDS = 220;
    const COMMAND_LEDS_READ_LENGTH = 1;
    const COMMAND_POWER_LED = 221;
    const COMMAND_POWER_LED_READ_LENGTH = 1;
    const COMMAND_ERROR_LED = 222;
    const COMMAND_ERROR_LED_READ_LENGTH = 1;
    const COMMAND_CONNECT_LED = 223;
    const COMMAND_CONNECT_LED_READ_LENGTH = 1;
    const COMMAND_TRANSRECEIVE_LED = 224;
    const COMMAND_TRANSRECEIVE_LED_READ_LENGTH = 1;
    const COMMAND_TRANSCEIVE_LED = 225;
    const COMMAND_TRANSCEIVE_LED_READ_LENGTH = 1;
    const COMMAND_RECEIVE_LED = 226;
    const COMMAND_RECEIVE_LED_READ_LENGTH = 1;
    const COMMAND_CUSTOM_LED = 227;
    const COMMAND_CUSTOM_LED_READ_LENGTH = 1;
    const COMMAND_RGB_LED = 228;
    const COMMAND_RGB_LED_READ_LENGTH = 9;
    const COMMAND_ALL_LEDS = 229;
    const COMMAND_ALL_LEDS_READ_LENGTH = 1;
    const COMMAND_STATUS = 250;
    const COMMAND_DATA_CHANGED = 251;

    const POWER_LED_BIT = 7;
    const ERROR_LED_BIT = 6;
    const CONNECT_LED_BIT = 5;
    const TRANSRECEIVE_LED_BIT = 4;
    const TRANSCEIVE_LED_BIT = 3;
    const RECEIVE_LED_BIT = 2;
    const CUSTOM_LED_BIT = 1;
    const RGB_LED_BIT = 0;

    const POWER_LED_KEY = 'power';
    const ERROR_LED_KEY = 'error';
    const CONNECT_LED_KEY = 'connect';
    const TRANSRECEIVE_LED_KEY = 'transreceive';
    const TRANSCEIVE_LED_KEY = 'transceive';
    const RECEIVE_LED_KEY = 'receive';
    const CUSTOM_LED_KEY = 'custom';
    const RGB_LED_KEY = 'rgb';

    /**
     * @param Module $existingSlave
     */
    abstract public function onOverwriteExistingSlave(Module $existingSlave): void;

    /**
     * @throws SaveError
     * @throws SelectError
     * @throws ReceiveError
     * @throws AbstractException
     */
    public function handshake(): void
    {
        if (!is_null($this->slave->getId())) {
            $this->handshakeExistingDevice();
            return;
        }

        $this->handshakeNewDevice();
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     * @throws GetError
     */
    private function handshakeNewDevice(): void
    {
        $deviceId = $this->readDeviceId();
        $setAddress = $this->slave->getAddress();

        try {
            $this->slave = ModuleRepository::getByDeviceId($deviceId);
        } catch (SelectError $exception) {
            $this->slave->setDeviceId($deviceId);

            if (
                $deviceId == 0 ||
                $deviceId > self::MAX_DEVICE_ID
            ) {
                $this->writeDeviceId(ModuleRepository::getFreeDeviceId());
            }

            $this->slave->setAddress(Master::getNextFreeAddress($this->master->getModel()->getId()));
        }

        $address = $this->slave->getAddress();
        $this->slave->setAddress($setAddress);

        $this->writeAddress($address);

        $oldType = $this->slave->getTypeId();
        $this->readTypeId();

        if ($oldType != $this->slave->getTypeId()) {
            $this->slave->loadType();
        }

        $this->readHertz();
        $this->readBufferSize();
        $this->readEepromSize();
        $this->readPwmSpeed();
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    private function handshakeExistingDevice(): void
    {
        $this->master->send(MasterService::TYPE_SLAVE_IS_HC, chr($this->slave->getAddress()));
        $this->master->receiveReceiveReturn();

        (new LogModel())
            ->setMasterId($this->master->getModel()->getId())
            ->setType(MasterService::TYPE_SLAVE_IS_HC)
            ->setData(dechex($this->slave->getAddress()))
            ->setDirection(Server::DIRECTION_OUTPUT)
            ->save();

        if (!$this->slave->getHertz()) {
            $this->readHertz();
        }

        if (!$this->slave->getBufferSize()) {
            $this->readBufferSize();
        }

        if (!$this->slave->getEepromSize()) {
            $this->readEepromSize();
        }

        if (!$this->slave->getPwmSpeed()) {
            $this->readPwmSpeed();
        }
    }

    /**
     * @param int $command
     * @param string $data
     * @throws AbstractException
     */
    public function write(int $command, string $data = ''): void
    {
        // @todo Workaround. Server sendet bei einem Byte die Daten anders. Denk drüber nach!
        if (strlen($data) == 1) {
            $data .= 'a';
        }

        parent::write($command, $data);
    }

    /**
     * @param int $address
     * @throws AbstractException
     */
    public function writeAddress(int $address): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_ADDRESS, ['slave' => $this, 'newAddress' => $address]);

        $deviceId = $this->slave->getDeviceId();
        $this->write(self::COMMAND_ADDRESS, chr($deviceId >> 8) . chr($deviceId & 255) . chr($address));
        $this->master->scanBus();

        $this->event->fire(Hc::AFTER_WRITE_ADDRESS, ['slave' => $this, 'newAddress' => $address]);

        $this->slave->setAddress($address);
    }

    /**
     * @return int
     * @throws AbstractException
     */
    public function readDeviceId(): int
    {
        $data = $this->read(self::COMMAND_DEVICE_ID, self::COMMAND_DEVICE_ID_READ_LENGTH);
        $deviceId = (Transform::asciiToInt($data, 0) << 8) | Transform::asciiToInt($data, 1);

        $this->event->fire(Hc::READ_DEVICE_ID, ['slave' => $this, 'deviceId' => $deviceId]);

        $this->slave->setDeviceId($deviceId);

        return $deviceId;
    }

    /**
     * @param int $deviceId
     * @throws AbstractException
     */
    public function writeDeviceId(int $deviceId): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_DEVICE_ID, ['slave' => $this, 'newDeviceId' => $deviceId]);

        $currentDeviceId = $this->slave->getDeviceId();
        $this->write(
            self::COMMAND_DEVICE_ID,
            chr($currentDeviceId >> 8) . chr($currentDeviceId & 255) .
            chr($deviceId >> 8) . chr($deviceId & 255)
        );

        $this->event->fire(Hc::AFTER_WRITE_ADDRESS, ['slave' => $this, 'newDeviceId' => $deviceId]);

        $this->slave->setDeviceId($deviceId);
    }

    /**
     * @return int
     * @throws AbstractException
     */
    public function readTypeId(): int
    {
        $data = $this->read(self::COMMAND_TYPE, self::COMMAND_TYPE_READ_LENGTH);
        $typeId = Transform::asciiToInt($data, 0);

        $this->event->fire(Hc::READ_TYPE, ['slave' => $this, 'typeId' => $typeId]);

        $this->slave->setTypeId($typeId);
        $this->slave->loadType();

        return $typeId;
    }

    /**
     * @param TypeModel $type
     * @return AbstractSlave
     * @throws AbstractException
     */
    public function writeType(TypeModel $type): AbstractSlave
    {
        $this->event->fire(Hc::BEFORE_WRITE_TYPE, ['slave' => $this, 'typeId' => $type->getId()]);

        $this->write(self::COMMAND_TYPE, chr($type->getId()));

        $this->event->fire(Hc::AFTER_WRITE_TYPE, ['slave' => $this, 'typeId' => $type->getId()]);

        $this->slave->setType($type);

        $slave = SlaveFactory::create($this->slave, $this->master);
        $slave->handshake();

        return $slave;
    }

    /**
     * @throws AbstractException
     */
    public function writeRestart(): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_RESTART, ['slave' => $this]);

        $deviceId = $this->slave->getDeviceId();
        $this->write(self::COMMAND_RESTART, chr($deviceId >> 8) . chr($deviceId & 255));

        $this->event->fire(Hc::AFTER_WRITE_RESTART, ['slave' => $this]);
    }

    /**
     * @param int $speed
     * @throws AbstractException
     */
    public function writePwmSpeed(int $speed): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_PWM_SPEED, ['slave' => $this, 'speed' => $speed]);

        $this->write(self::COMMAND_PWM_SPEED, chr($speed>>8) . chr($speed));

        $this->event->fire(Hc::AFTER_WRITE_PWM_SPEED, ['slave' => $this, 'speed' => $speed]);

        $this->slave->setPwmSpeed($speed);
    }

    /**
     * @param int $length
     * @return string
     * @throws AbstractException
     * @throws ReceiveError
     */
    protected function readConfig(int $length): string
    {
        $config = $this->read(self::COMMAND_CONFIGURATION, $length);

        $this->event->fire(Hc::READ_CONFIG, ['slave' => $this, 'config' => $config]);

        return $config;
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readHertz(): int
    {
        $data = $this->read(self::COMMAND_HERTZ, self::COMMAND_HERTZ_READ_LENGTH);
        $hertz = Transform::asciiToInt($data);

        $this->event->fire(Hc::READ_HERTZ, ['slave' => $this, 'hertz' => $hertz]);

        $this->slave->setHertz($hertz);

        return $hertz;
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readPwmSpeed(): int
    {
        $data = $this->read(self::COMMAND_PWM_SPEED, self::COMMAND_PWM_SPEED_READ_LENGTH);
        $speed = Transform::asciiToInt($data);

        $this->event->fire(Hc::READ_PWM_SPEED, ['slave' => $this, 'speed' => $speed]);

        $this->slave->setPwmSpeed($speed);

        return $speed;
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readEepromSize(): int
    {
        $data = $this->read(self::COMMAND_EEPROM_SIZE, self::COMMAND_EEPROM_SIZE_READ_LENGTH);
        $eepromSize = Transform::asciiToInt($data);

        $this->event->fire(Hc::READ_EEPROM_SIZE, ['slave' => $this, 'eepromSize' => $eepromSize]);

        $this->slave->setEepromSize($eepromSize);

        return $eepromSize;
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readEepromFree(): int
    {
        $data = $this->read(self::COMMAND_EEPROM_FREE, self::COMMAND_EEPROM_FREE_READ_LENGTH);
        $eepromFree = Transform::asciiToInt($data);

        $this->event->fire(Hc::READ_EEPROM_FREE, ['slave' => $this, 'eepromFree' => $eepromFree]);

        return $eepromFree;
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readEepromPosition(): int
    {
        $data = $this->read(self::COMMAND_EEPROM_POSITION, self::COMMAND_EEPROM_POSITION_READ_LENGTH);
        $eepromPosition = Transform::asciiToInt($data);

        $this->event->fire(Hc::READ_EEPROM_POSITION, ['slave' => $this, 'eepromPosition' => $eepromPosition]);

        return $eepromPosition;
    }

    /**
     * @param int $position
     * @throws AbstractException
     */
    public function writeEepromPosition(int $position): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_EEPROM_POSITION, ['slave' => $this, 'eepromPosition' => $position]);

        $this->write(self::COMMAND_EEPROM_POSITION, chr($position >> 8) . chr($position & 255));

        $this->event->fire(Hc::AFTER_WRITE_EEPROM_POSITION, ['slave' => $this, 'eepromPosition' => $position]);
    }

    /**
     * @throws AbstractException
     */
    public function writeEepromErase(): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_EEPROM_ERASE, ['slave' => $this]);

        $deviceId = $this->slave->getDeviceId();
        $this->write(self::COMMAND_EEPROM_ERASE, chr($deviceId >> 8) . chr($deviceId & 255));

        $this->event->fire(Hc::AFTER_WRITE_EEPROM_ERASE, ['slave' => $this]);
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readBufferSize(): int
    {
        $data = $this->read(self::COMMAND_BUFFER_SIZE, self::COMMAND_BUFFER_SIZE_READ_LENGTH);
        $bufferSize = Transform::asciiToInt($data);

        $this->event->fire(Hc::READ_BUFFER_SIZE, ['slave' => $this, 'bufferSize' => $bufferSize]);

        $this->slave->setBufferSize($bufferSize);

        return $bufferSize;
    }

    /**
     * @return array
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readLedStatus(): array
    {
        $leds = Transform::asciiToInt($this->read(self::COMMAND_LEDS, self::COMMAND_LEDS_READ_LENGTH));
        $ledStatus = [
            self::POWER_LED_KEY => (bool)(($leds >> self::POWER_LED_BIT) & 1),
            self::ERROR_LED_KEY => (bool)(($leds >> self::ERROR_LED_BIT) & 1),
            self::CONNECT_LED_KEY => (bool)(($leds >> self::CONNECT_LED_BIT) & 1),
            self::TRANSRECEIVE_LED_KEY => (bool)(($leds >> self::TRANSRECEIVE_LED_BIT) & 1),
            self::TRANSCEIVE_LED_KEY => (bool)(($leds >> self::TRANSCEIVE_LED_BIT) & 1),
            self::RECEIVE_LED_KEY => (bool)(($leds >> self::RECEIVE_LED_BIT) & 1),
            self::CUSTOM_LED_KEY => (bool)(($leds >> self::CUSTOM_LED_BIT) & 1),
            self::RGB_LED_KEY => (bool)(($leds >> self::RGB_LED_BIT) & 1)
        ];

        $eventData = $ledStatus;
        $eventData['slave'] = $this;
        $this->event->fire(Hc::READ_LED_STATUS, $eventData);

        return $ledStatus;
    }

    /**
     * @param bool $on
     * @throws AbstractException
     */
    public function writePowerLed(bool $on): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_POWER_LED, ['slave' => $this, 'on' => $on]);

        $this->write(self::COMMAND_POWER_LED, chr((int)$on));

        $this->event->fire(Hc::AFTER_WRITE_POWER_LED, ['slave' => $this, 'on' => $on]);
    }

    /**
     * @param bool $on
     * @throws AbstractException
     */
    public function writeErrorLed(bool $on): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_ERROR_LED, ['slave' => $this, 'on' => $on]);

        $this->write(self::COMMAND_ERROR_LED, chr((int)$on));

        $this->event->fire(Hc::AFTER_WRITE_ERROR_LED, ['slave' => $this, 'on' => $on]);
    }

    /**
     * @param bool $on
     * @throws AbstractException
     */
    public function writeConnectLed(bool $on): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_CONNECT_LED, ['slave' => $this, 'on' => $on]);

        $this->write(self::COMMAND_CONNECT_LED, chr((int)$on));

        $this->event->fire(Hc::AFTER_WRITE_CONNECT_LED, ['slave' => $this, 'on' => $on]);
    }

    /**
     * @param bool $on
     * @throws AbstractException
     */
    public function writeTransreceiveLed(bool $on): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_TRANSRECEIVE_LED, ['slave' => $this, 'on' => $on]);

        $this->write(self::COMMAND_TRANSRECEIVE_LED, chr((int)$on));

        $this->event->fire(Hc::AFTER_WRITE_TRANSRECEIVE_LED, ['slave' => $this, 'on' => $on]);
    }

    /**
     * @param bool $on
     * @throws AbstractException
     */
    public function writeTransceiveLed(bool $on): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_TRANSCEIVE_LED, ['slave' => $this, 'on' => $on]);

        $this->write(self::COMMAND_TRANSCEIVE_LED, chr((int)$on));

        $this->event->fire(Hc::AFTER_WRITE_TRANSCEIVE_LED, ['slave' => $this, 'on' => $on]);
    }

    /**
     * @param bool $on
     * @throws AbstractException
     */
    public function writeReceiveLed(bool $on): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_RECEIVE_LED, ['slave' => $this, 'on' => $on]);

        $this->write(self::COMMAND_RECEIVE_LED, chr((int)$on));

        $this->event->fire(Hc::AFTER_WRITE_RECEIVE_LED, ['slave' => $this, 'on' => $on]);
    }

    /**
     * @param bool $on
     * @throws AbstractException
     */
    public function writeCustomLed(bool $on): void
    {
        $this->event->fire(Hc::BEFORE_WRITE_CUSTOM_LED, ['slave' => $this, 'on' => $on]);

        $this->write(self::COMMAND_CUSTOM_LED, chr((int)$on));

        $this->event->fire(Hc::AFTER_WRITE_CUSTOM_LED, ['slave' => $this, 'on' => $on]);
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readPowerLed(): bool
    {
        $on = (bool)Transform::asciiToInt($this->read(
            self::COMMAND_POWER_LED,
            self::COMMAND_POWER_LED_READ_LENGTH)
        );

        $this->event->fire(Hc::READ_POWER_LED, ['slave' => $this, 'on' => $on]);

        return $on;
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readErrorLed(): bool
    {
        $on = (bool)Transform::asciiToInt($this->read(
            self::COMMAND_ERROR_LED,
            self::COMMAND_ERROR_LED_READ_LENGTH)
        );

        $this->event->fire(Hc::READ_ERROR_LED, ['slave' => $this, 'on' => $on]);

        return $on;
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readConnectLed(): bool
    {
        $on = (bool)Transform::asciiToInt($this->read(
            self::COMMAND_CONNECT_LED,
            self::COMMAND_CONNECT_LED_READ_LENGTH)
        );

        $this->event->fire(Hc::READ_CONNECT_LED, ['slave' => $this, 'on' => $on]);

        return $on;
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readTransreceiveLed(): bool
    {
        $on =(bool)Transform::asciiToInt($this->read(
            self::COMMAND_TRANSRECEIVE_LED,
            self::COMMAND_TRANSRECEIVE_LED_READ_LENGTH)
        );

        $this->event->fire(Hc::READ_TRANSRECEIVE_LED, ['slave' => $this, 'on' => $on]);

        return $on;
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readTransceiveLed(): bool
    {
        $on = (bool)Transform::asciiToInt($this->read(
            self::COMMAND_TRANSCEIVE_LED,
            self::COMMAND_TRANSCEIVE_LED_READ_LENGTH)
        );

        $this->event->fire(Hc::READ_TRANSCEIVE_LED, ['slave' => $this, 'on' => $on]);

        return $on;
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readReceiveLed(): bool
    {
        $on = (bool)Transform::asciiToInt($this->read(
            self::COMMAND_RECEIVE_LED,
            self::COMMAND_RECEIVE_LED_READ_LENGTH)
        );

        $this->event->fire(Hc::READ_RECEIVE_LED, ['slave' => $this, 'on' => $on]);

        return $on;
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readCustomLed(): bool
    {
        $on = (bool)Transform::asciiToInt($this->read(
            self::COMMAND_CUSTOM_LED,
            self::COMMAND_CUSTOM_LED_READ_LENGTH)
        );

        $this->event->fire(Hc::READ_CUSTOM_LED, ['slave' => $this, 'on' => $on]);

        return $on;
    }

    /**
     * @param string $power
     * @param string $error
     * @param string $connect
     * @param string $transceive
     * @param string $receive
     * @param string $custom
     * @throws AbstractException
     */
    public function writeRgbLed(
        string $power,
        string $error,
        string $connect,
        string $transceive,
        string $receive,
        string $custom
    ): void {
        $colors = [
            self::POWER_LED_KEY => $power,
            self::ERROR_LED_KEY => $error,
            self::CONNECT_LED_KEY => $connect,
            self::TRANSCEIVE_LED_KEY => $transceive,
            self::RECEIVE_LED_KEY => $receive,
            self::CUSTOM_LED_KEY => $custom
        ];

        $eventData = $colors;
        $eventData['slave'] = $this;
        $this->event->fire(Hc::BEFORE_WRITE_RGB_LED, $eventData);

        $power = Transform::hexToInt($power);
        $error = Transform::hexToInt($error);
        $connect = Transform::hexToInt($connect);
        $transceive = Transform::hexToInt($transceive);
        $receive = Transform::hexToInt($receive);
        $custom = Transform::hexToInt($custom);

        $this->write(
            self::COMMAND_RGB_LED,
            chr($power >> 4) .
            chr((($power << 4) | ($error >> 8)) & 255) .
            chr($error & 255) .
            chr($connect >> 4) .
            chr((($connect << 4) | ($transceive >> 8)) & 255) .
            chr($transceive & 255) .
            chr($receive >> 4) .
            chr((($receive << 4) | ($custom >> 8)) & 255) .
            chr($custom & 255)
        );

        $this->event->fire(Hc::AFTER_WRITE_RGB_LED, $eventData);
    }

    /**
     * @return array
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readRgbLed(): array
    {
        $rgbLed = Transform::asciiToHex($this->read(self::COMMAND_RGB_LED, self::COMMAND_RGB_LED_READ_LENGTH));
        $colors = [
            self::POWER_LED_KEY => mb_substr($rgbLed, 0, 3),
            self::ERROR_LED_KEY => mb_substr($rgbLed, 3, 3),
            self::CONNECT_LED_KEY => mb_substr($rgbLed, 6, 3),
            self::TRANSCEIVE_LED_KEY => mb_substr($rgbLed, 9, 3),
            self::RECEIVE_LED_KEY => mb_substr($rgbLed, 12, 3),
            self::CUSTOM_LED_KEY => mb_substr($rgbLed, 15, 3)
        ];

        $eventData = $colors;
        $eventData['slave'] = $this;
        $this->event->fire(Hc::READ_RGB_LED, $eventData);

        return $colors;
    }

    /**
     * @param bool $power
     * @param bool $error
     * @param bool $connect
     * @param bool $transreceive
     * @param bool $transceive
     * @param bool $receive
     * @param bool $custom
     * @throws AbstractException
     */
    public function writeAllLeds(
        bool $power,
        bool $error,
        bool $connect,
        bool $transreceive,
        bool $transceive,
        bool $receive,
        bool $custom
    ): void {
        $leds = [
            self::POWER_LED_KEY => $power,
            self::ERROR_LED_KEY => $error,
            self::CONNECT_LED_KEY => $connect,
            self::TRANSRECEIVE_LED_KEY => $transreceive,
            self::TRANSCEIVE_LED_KEY => $transceive,
            self::RECEIVE_LED_KEY => $receive,
            self::CUSTOM_LED_KEY => $custom
        ];

        $eventData = $leds;
        $eventData['slave'] = $this;
        $this->event->fire(Hc::BEFORE_WRITE_ALL_LEDS, $eventData);

        $this->write(
            self::COMMAND_ALL_LEDS,
            chr(
                (((int)$power) << self::POWER_LED_BIT) |
                (((int)$error) << self::ERROR_LED_BIT) |
                (((int)$connect) << self::CONNECT_LED_BIT) |
                (((int)$transreceive) << self::TRANSRECEIVE_LED_BIT) |
                (((int)$transceive) << self::TRANSCEIVE_LED_BIT) |
                (((int)$receive) << self::RECEIVE_LED_BIT) |
                (((int)$custom) << self::CUSTOM_LED_BIT)
            )
        );

        $this->event->fire(Hc::AFTER_WRITE_ALL_LEDS, $eventData);
    }

    /**
     * @return array
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readAllLeds(): array
    {
        $leds = Transform::asciiToInt($this->read(
            self::COMMAND_ALL_LEDS,
            self::COMMAND_ALL_LEDS_READ_LENGTH)
        );

        $leds = [
            self::POWER_LED_KEY => (bool)(($leds >> self::POWER_LED_BIT) & 1),
            self::ERROR_LED_KEY => (bool)(($leds >> self::ERROR_LED_BIT) & 1),
            self::CONNECT_LED_KEY => (bool)(($leds >> self::CONNECT_LED_BIT) & 1),
            self::TRANSRECEIVE_LED_KEY => (bool)(($leds >> self::TRANSRECEIVE_LED_BIT) & 1),
            self::TRANSCEIVE_LED_KEY => (bool)(($leds >> self::TRANSCEIVE_LED_BIT) & 1),
            self::RECEIVE_LED_KEY => (bool)(($leds >> self::RECEIVE_LED_BIT) & 1),
            self::CUSTOM_LED_KEY => (bool)(($leds >> self::CUSTOM_LED_BIT) & 1)
        ];

        $eventData = $leds;
        $eventData['slave'] = $this;
        $this->event->fire(Hc::READ_ALL_LEDS, $eventData);

        return $leds;
    }
}