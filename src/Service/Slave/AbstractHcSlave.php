<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\FileNotFound;
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
use GibsonOS\Module\Hc\Service\Event\Describer\HcService;
use GibsonOS\Module\Hc\Service\MasterService as MasterService;
use GibsonOS\Module\Hc\Service\ServerService;

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
     * @param Module $slave
     * @param Module $existingSlave
     *
     * @return Module
     */
    abstract public function onOverwriteExistingSlave(Module $slave, Module $existingSlave): Module;

    /**
     * @param Module $slave
     * @param int    $type
     * @param int    $command
     * @param string $data
     */
    abstract public function receive(Module $slave, int $type, int $command, string $data): void;

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws GetError
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     *
     * @return Module
     */
    public function handshake(Module $slave): Module
    {
        if ($slave->getId() !== null) {
            $this->handshakeExistingDevice($slave);

            return $slave;
        }

        return $this->handshakeNewDevice($slave);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws GetError
     * @throws ReceiveError
     * @throws SelectError
     *
     * @return Module
     */
    private function handshakeNewDevice(Module $slave): Module
    {
        $deviceId = $this->readDeviceId($slave);
        $setAddress = $slave->getAddress();

        try {
            $slave = ModuleRepository::getByDeviceId($deviceId);
        } catch (SelectError $exception) {
            $slave->setDeviceId($deviceId);

            if (
                $deviceId == 0 ||
                $deviceId > self::MAX_DEVICE_ID
            ) {
                $this->writeDeviceId($slave, ModuleRepository::getFreeDeviceId());
            }

            $slave->setAddress(Master::getNextFreeAddress((int) $slave->getMaster()->getId()));
        }

        $address = (int) $slave->getAddress();
        $slave->setAddress($setAddress);

        $this->writeAddress($slave, $address);

        $oldType = $slave->getTypeId();
        $this->readTypeId($slave);

        if ($oldType !== $slave->getTypeId()) {
            $slave->loadType();
        }

        $this->readHertz($slave);
        $this->readBufferSize($slave);
        $this->readEepromSize($slave);
        $this->readPwmSpeed($slave);

        return $slave;
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     * @throws DateTimeError
     */
    private function handshakeExistingDevice(Module $slave): void
    {
        $this->master->send($slave->getMaster(), MasterService::TYPE_SLAVE_IS_HC, chr((int) $slave->getAddress()));
        $this->master->receiveReceiveReturn($slave->getMaster());

        (new LogModel())
            ->setMasterId($slave->getMaster()->getId())
            ->setType(MasterService::TYPE_SLAVE_IS_HC)
            ->setData(dechex((int) $slave->getAddress()))
            ->setDirection(ServerService::DIRECTION_OUTPUT)
            ->save();

        if (empty($slave->getHertz())) {
            $this->readHertz($slave);
        }

        if (empty($slave->getBufferSize())) {
            $this->readBufferSize($slave);
        }

        if (empty($slave->getEepromSize())) {
            $this->readEepromSize($slave);
        }

        if (empty($slave->getPwmSpeed())) {
            $this->readPwmSpeed($slave);
        }
    }

    /**
     * @param Module $slave
     * @param int    $command
     * @param string $data
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function write(Module $slave, int $command, string $data = ''): void
    {
        // @todo Workaround. Server sendet bei einem Byte die Daten anders. Denk drüber nach!
        if (strlen($data) == 1) {
            $data .= 'a';
        }

        parent::write($slave, $command, $data);
    }

    /**
     * @param Module $slave
     * @param int    $address
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeAddress(Module $slave, int $address): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_ADDRESS, ['slave' => $slave, 'newAddress' => $address]);

        $deviceId = $slave->getDeviceId();
        $this->write(
            $slave,
            self::COMMAND_ADDRESS,
            chr($deviceId >> 8) . chr($deviceId & 255) . chr($address)
        );
        $this->master->scanBus($slave->getMaster());

        $this->event->fire(HcService::AFTER_WRITE_ADDRESS, ['slave' => $slave, 'newAddress' => $address]);

        $slave->setAddress($address);
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
        $data = $this->read($slave, self::COMMAND_DEVICE_ID, self::COMMAND_DEVICE_ID_READ_LENGTH);
        $deviceId = ($this->transform->asciiToInt($data, 0) << 8) | $this->transform->asciiToInt($data, 1);

        $this->event->fire(HcService::READ_DEVICE_ID, ['slave' => $slave, 'deviceId' => $deviceId]);

        return $deviceId;
    }

    /**
     * @param int $deviceId
     *
     * @throws AbstractException
     */
    public function writeDeviceId(Module $slave, int $deviceId): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_DEVICE_ID, ['slave' => $slave, 'newDeviceId' => $deviceId]);

        $currentDeviceId = $slave->getDeviceId();
        $this->write(
            $slave,
            self::COMMAND_DEVICE_ID,
            chr($currentDeviceId >> 8) . chr($currentDeviceId & 255) .
            chr($deviceId >> 8) . chr($deviceId & 255)
        );

        $this->event->fire(HcService::AFTER_WRITE_ADDRESS, ['slave' => $slave, 'newDeviceId' => $deviceId]);
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
        $data = $this->read($slave, self::COMMAND_TYPE, self::COMMAND_TYPE_READ_LENGTH);
        $typeId = $this->transform->asciiToInt($data, 0);

        $this->event->fire(HcService::READ_TYPE, ['slave' => $slave, 'typeId' => $typeId]);

        return $typeId;
    }

    /**
     * @param Module    $slave
     * @param TypeModel $type
     *
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws FileNotFound
     *
     * @return AbstractSlave
     */
    public function writeType(Module $slave, TypeModel $type): AbstractSlave
    {
        $this->event->fire(HcService::BEFORE_WRITE_TYPE, ['slave' => $slave, 'typeId' => $type->getId()]);

        $this->write($slave, self::COMMAND_TYPE, chr((int) $type->getId()));

        $this->event->fire(HcService::AFTER_WRITE_TYPE, ['slave' => $slave, 'typeId' => $type->getId()]);

        $slaveService = SlaveFactory::create();
        $slaveService->handshake($slave);

        return $slaveService;
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeRestart(Module $slave): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_RESTART, ['slave' => $slave]);

        $deviceId = $slave->getDeviceId();
        $this->write(
            $slave,
            self::COMMAND_RESTART,
            chr($deviceId >> 8) . chr($deviceId & 255)
        );

        $this->event->fire(HcService::AFTER_WRITE_RESTART, ['slave' => $slave]);
    }

    /**
     * @param Module $slave
     * @param int    $speed
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writePwmSpeed(Module $slave, int $speed): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_PWM_SPEED, ['slave' => $slave, 'speed' => $speed]);

        $this->write(
            $slave,
            self::COMMAND_PWM_SPEED,
            chr($speed >> 8) . chr($speed)
        );

        $this->event->fire(HcService::AFTER_WRITE_PWM_SPEED, ['slave' => $slave, 'speed' => $speed]);
    }

    /**
     * @param Module $slave
     * @param int    $length
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return string
     */
    protected function readConfig(Module $slave, int $length): string
    {
        $config = $this->read($slave, self::COMMAND_CONFIGURATION, $length);

        $this->event->fire(HcService::READ_CONFIG, ['slave' => $slave, 'config' => $config]);

        return $config;
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
        $data = $this->read($slave, self::COMMAND_HERTZ, self::COMMAND_HERTZ_READ_LENGTH);
        $hertz = $this->transform->asciiToInt($data);

        $this->event->fire(HcService::READ_HERTZ, ['slave' => $slave, 'hertz' => $hertz]);

        return $hertz;
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
        $data = $this->read($slave, self::COMMAND_PWM_SPEED, self::COMMAND_PWM_SPEED_READ_LENGTH);
        $speed = $this->transform->asciiToInt($data);

        $this->event->fire(HcService::READ_PWM_SPEED, ['slave' => $slave, 'speed' => $speed]);

        return $speed;
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
        $data = $this->read($slave, self::COMMAND_EEPROM_SIZE, self::COMMAND_EEPROM_SIZE_READ_LENGTH);
        $eepromSize = $this->transform->asciiToInt($data);

        $this->event->fire(HcService::READ_EEPROM_SIZE, ['slave' => $slave, 'eepromSize' => $eepromSize]);

        return $eepromSize;
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
        $data = $this->read($slave, self::COMMAND_EEPROM_FREE, self::COMMAND_EEPROM_FREE_READ_LENGTH);
        $eepromFree = $this->transform->asciiToInt($data);

        $this->event->fire(HcService::READ_EEPROM_FREE, ['slave' => $this, 'eepromFree' => $eepromFree]);

        return $eepromFree;
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
        $data = $this->read($slave, self::COMMAND_EEPROM_POSITION, self::COMMAND_EEPROM_POSITION_READ_LENGTH);
        $eepromPosition = $this->transform->asciiToInt($data);

        $this->event->fire(HcService::READ_EEPROM_POSITION, ['slave' => $slave, 'eepromPosition' => $eepromPosition]);

        return $eepromPosition;
    }

    /**
     * @param Module $slave
     * @param int    $position
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeEepromPosition(Module $slave, int $position): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_EEPROM_POSITION, ['slave' => $slave, 'eepromPosition' => $position]);

        $this->write(
            $slave,
            self::COMMAND_EEPROM_POSITION,
            chr($position >> 8) . chr($position & 255)
        );

        $this->event->fire(HcService::AFTER_WRITE_EEPROM_POSITION, ['slave' => $slave, 'eepromPosition' => $position]);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeEepromErase(Module $slave): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_EEPROM_ERASE, ['slave' => $slave]);

        $deviceId = $slave->getDeviceId();
        $this->write(
            $slave,
            self::COMMAND_EEPROM_ERASE,
            chr($deviceId >> 8) . chr($deviceId & 255)
        );

        $this->event->fire(HcService::AFTER_WRITE_EEPROM_ERASE, ['slave' => $slave]);
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
        $data = $this->read($slave, self::COMMAND_BUFFER_SIZE, self::COMMAND_BUFFER_SIZE_READ_LENGTH);
        $bufferSize = $this->transform->asciiToInt($data);

        $this->event->fire(HcService::READ_BUFFER_SIZE, ['slave' => $slave, 'bufferSize' => $bufferSize]);

        return $bufferSize;
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
        $leds = $this->transform->asciiToInt($this->read(
            $slave,
            self::COMMAND_LEDS,
            self::COMMAND_LEDS_READ_LENGTH
        ));
        $ledStatus = [
            self::POWER_LED_KEY => (bool) (($leds >> self::POWER_LED_BIT) & 1),
            self::ERROR_LED_KEY => (bool) (($leds >> self::ERROR_LED_BIT) & 1),
            self::CONNECT_LED_KEY => (bool) (($leds >> self::CONNECT_LED_BIT) & 1),
            self::TRANSRECEIVE_LED_KEY => (bool) (($leds >> self::TRANSRECEIVE_LED_BIT) & 1),
            self::TRANSCEIVE_LED_KEY => (bool) (($leds >> self::TRANSCEIVE_LED_BIT) & 1),
            self::RECEIVE_LED_KEY => (bool) (($leds >> self::RECEIVE_LED_BIT) & 1),
            self::CUSTOM_LED_KEY => (bool) (($leds >> self::CUSTOM_LED_BIT) & 1),
            self::RGB_LED_KEY => (bool) (($leds >> self::RGB_LED_BIT) & 1),
        ];

        $eventData = $ledStatus;
        $eventData['slave'] = $slave;
        $this->event->fire(HcService::READ_LED_STATUS, $eventData);

        return $ledStatus;
    }

    /**
     * @param Module $slave
     * @param bool   $on
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writePowerLed(Module $slave, bool $on): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_POWER_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_POWER_LED, chr((int) $on));

        $this->event->fire(HcService::AFTER_WRITE_POWER_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @param Module $slave
     * @param bool   $on
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeErrorLed(Module $slave, bool $on): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_ERROR_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_ERROR_LED, chr((int) $on));

        $this->event->fire(HcService::AFTER_WRITE_ERROR_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @param Module $slave
     * @param bool   $on
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeConnectLed(Module $slave, bool $on): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_CONNECT_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_CONNECT_LED, chr((int) $on));

        $this->event->fire(HcService::AFTER_WRITE_CONNECT_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @param Module $slave
     * @param bool   $on
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeTransreceiveLed(Module $slave, bool $on): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_TRANSRECEIVE_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_TRANSRECEIVE_LED, chr((int) $on));

        $this->event->fire(HcService::AFTER_WRITE_TRANSRECEIVE_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @param Module $slave
     * @param bool   $on
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeTransceiveLed(Module $slave, bool $on): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_TRANSCEIVE_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_TRANSCEIVE_LED, chr((int) $on));

        $this->event->fire(HcService::AFTER_WRITE_TRANSCEIVE_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @param Module $slave
     * @param bool   $on
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeReceiveLed(Module $slave, bool $on): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_RECEIVE_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_RECEIVE_LED, chr((int) $on));

        $this->event->fire(HcService::AFTER_WRITE_RECEIVE_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @param Module $slave
     * @param bool   $on
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeCustomLed(Module $slave, bool $on): void
    {
        $this->event->fire(HcService::BEFORE_WRITE_CUSTOM_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_CUSTOM_LED, chr((int) $on));

        $this->event->fire(HcService::AFTER_WRITE_CUSTOM_LED, ['slave' => $slave, 'on' => $on]);
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
        $on = (bool) $this->transform->asciiToInt(
            $this->read(
                $slave,
                self::COMMAND_POWER_LED,
                self::COMMAND_POWER_LED_READ_LENGTH
            )
        );

        $this->event->fire(HcService::READ_POWER_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
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
        $on = (bool) $this->transform->asciiToInt(
            $this->read(
                $slave,
                self::COMMAND_ERROR_LED,
                self::COMMAND_ERROR_LED_READ_LENGTH
            )
        );

        $this->event->fire(HcService::READ_ERROR_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
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
        $on = (bool) $this->transform->asciiToInt(
            $this->read(
                $slave,
                self::COMMAND_CONNECT_LED,
                self::COMMAND_CONNECT_LED_READ_LENGTH
            )
        );

        $this->event->fire(HcService::READ_CONNECT_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
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
        $on = (bool) $this->transform->asciiToInt(
            $this->read(
                $slave,
                self::COMMAND_TRANSRECEIVE_LED,
                self::COMMAND_TRANSRECEIVE_LED_READ_LENGTH
            )
        );

        $this->event->fire(HcService::READ_TRANSRECEIVE_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
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
        $on = (bool) $this->transform->asciiToInt(
            $this->read(
                $slave,
                self::COMMAND_TRANSCEIVE_LED,
                self::COMMAND_TRANSCEIVE_LED_READ_LENGTH
            )
        );

        $this->event->fire(HcService::READ_TRANSCEIVE_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
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
        $on = (bool) $this->transform->asciiToInt(
            $this->read(
                $slave,
                self::COMMAND_RECEIVE_LED,
                self::COMMAND_RECEIVE_LED_READ_LENGTH
            )
        );

        $this->event->fire(HcService::READ_RECEIVE_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
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
        $on = (bool) $this->transform->asciiToInt(
            $this->read(
                $slave,
                self::COMMAND_CUSTOM_LED,
                self::COMMAND_CUSTOM_LED_READ_LENGTH
            )
        );

        $this->event->fire(HcService::READ_CUSTOM_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
    }

    /**
     * @param Module $slave
     * @param string $power
     * @param string $error
     * @param string $connect
     * @param string $transceive
     * @param string $receive
     * @param string $custom
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeRgbLed(
        Module $slave,
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
            self::CUSTOM_LED_KEY => $custom,
        ];

        $eventData = $colors;
        $eventData['slave'] = $slave;
        $this->event->fire(HcService::BEFORE_WRITE_RGB_LED, $eventData);

        $power = $this->transform->hexToInt($power);
        $error = $this->transform->hexToInt($error);
        $connect = $this->transform->hexToInt($connect);
        $transceive = $this->transform->hexToInt($transceive);
        $receive = $this->transform->hexToInt($receive);
        $custom = $this->transform->hexToInt($custom);

        $this->write(
            $slave,
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

        $this->event->fire(HcService::AFTER_WRITE_RGB_LED, $eventData);
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
        $rgbLed = $this->transform->asciiToHex($this->read(
            $slave,
            self::COMMAND_RGB_LED,
            self::COMMAND_RGB_LED_READ_LENGTH
        ));
        $colors = [
            self::POWER_LED_KEY => mb_substr($rgbLed, 0, 3),
            self::ERROR_LED_KEY => mb_substr($rgbLed, 3, 3),
            self::CONNECT_LED_KEY => mb_substr($rgbLed, 6, 3),
            self::TRANSCEIVE_LED_KEY => mb_substr($rgbLed, 9, 3),
            self::RECEIVE_LED_KEY => mb_substr($rgbLed, 12, 3),
            self::CUSTOM_LED_KEY => mb_substr($rgbLed, 15, 3),
        ];

        $eventData = $colors;
        $eventData['slave'] = $slave;
        $this->event->fire(HcService::READ_RGB_LED, $eventData);

        return $colors;
    }

    /**
     * @param Module $slave
     * @param bool   $power
     * @param bool   $error
     * @param bool   $connect
     * @param bool   $transreceive
     * @param bool   $transceive
     * @param bool   $receive
     * @param bool   $custom
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeAllLeds(
        Module $slave,
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
            self::CUSTOM_LED_KEY => $custom,
        ];

        $eventData = $leds;
        $eventData['slave'] = $slave;
        $this->event->fire(HcService::BEFORE_WRITE_ALL_LEDS, $eventData);

        $this->write(
            $slave,
            self::COMMAND_ALL_LEDS,
            chr(
                (((int) $power) << self::POWER_LED_BIT) |
                (((int) $error) << self::ERROR_LED_BIT) |
                (((int) $connect) << self::CONNECT_LED_BIT) |
                (((int) $transreceive) << self::TRANSRECEIVE_LED_BIT) |
                (((int) $transceive) << self::TRANSCEIVE_LED_BIT) |
                (((int) $receive) << self::RECEIVE_LED_BIT) |
                (((int) $custom) << self::CUSTOM_LED_BIT)
            )
        );

        $this->event->fire(HcService::AFTER_WRITE_ALL_LEDS, $eventData);
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
        $leds = $this->transform->asciiToInt(
            $this->read(
                $slave,
                self::COMMAND_ALL_LEDS,
                self::COMMAND_ALL_LEDS_READ_LENGTH
            )
        );

        $leds = [
            self::POWER_LED_KEY => (bool) (($leds >> self::POWER_LED_BIT) & 1),
            self::ERROR_LED_KEY => (bool) (($leds >> self::ERROR_LED_BIT) & 1),
            self::CONNECT_LED_KEY => (bool) (($leds >> self::CONNECT_LED_BIT) & 1),
            self::TRANSRECEIVE_LED_KEY => (bool) (($leds >> self::TRANSRECEIVE_LED_BIT) & 1),
            self::TRANSCEIVE_LED_KEY => (bool) (($leds >> self::TRANSCEIVE_LED_BIT) & 1),
            self::RECEIVE_LED_KEY => (bool) (($leds >> self::RECEIVE_LED_BIT) & 1),
            self::CUSTOM_LED_KEY => (bool) (($leds >> self::CUSTOM_LED_BIT) & 1),
        ];

        $eventData = $leds;
        $eventData['slave'] = $slave;
        $this->event->fire(HcService::READ_ALL_LEDS, $eventData);

        return $leds;
    }
}
