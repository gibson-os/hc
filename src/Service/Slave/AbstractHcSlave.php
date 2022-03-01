<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\EventException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Event\AbstractHcEvent;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;
use JsonException;
use Psr\Log\LoggerInterface;
use ReflectionException;

abstract class AbstractHcSlave extends AbstractSlave
{
    public const MAX_DEVICE_ID = 65534;

    public const COMMAND_DEVICE_ID = 200;

    private const COMMAND_DEVICE_ID_READ_LENGTH = 2;

    public const COMMAND_TYPE = 201;

    private const COMMAND_TYPE_READ_LENGTH = 1;

    public const COMMAND_ADDRESS = 202;

    public const COMMAND_RESTART = 209;

    public const COMMAND_CONFIGURATION = 210;

    public const COMMAND_HERTZ = 211;

    public const COMMAND_HERTZ_READ_LENGTH = 4;

    public const COMMAND_EEPROM_SIZE = 212;

    private const COMMAND_EEPROM_SIZE_READ_LENGTH = 2;

    public const COMMAND_EEPROM_FREE = 213;

    private const COMMAND_EEPROM_FREE_READ_LENGTH = 2;

    public const COMMAND_EEPROM_POSITION = 214;

    private const COMMAND_EEPROM_POSITION_READ_LENGTH = 2;

    public const COMMAND_EEPROM_ERASE = 215;

    public const COMMAND_BUFFER_SIZE = 216;

    private const COMMAND_BUFFER_SIZE_READ_LENGTH = 2;

    public const COMMAND_PWM_SPEED = 217;

    private const COMMAND_PWM_SPEED_READ_LENGTH = 2;

    public const COMMAND_LEDS = 220;

    private const COMMAND_LEDS_READ_LENGTH = 1;

    public const COMMAND_POWER_LED = 221;

    private const COMMAND_POWER_LED_READ_LENGTH = 1;

    public const COMMAND_ERROR_LED = 222;

    private const COMMAND_ERROR_LED_READ_LENGTH = 1;

    public const COMMAND_CONNECT_LED = 223;

    private const COMMAND_CONNECT_LED_READ_LENGTH = 1;

    public const COMMAND_TRANSRECEIVE_LED = 224;

    private const COMMAND_TRANSRECEIVE_LED_READ_LENGTH = 1;

    public const COMMAND_TRANSCEIVE_LED = 225;

    private const COMMAND_TRANSCEIVE_LED_READ_LENGTH = 1;

    public const COMMAND_RECEIVE_LED = 226;

    private const COMMAND_RECEIVE_LED_READ_LENGTH = 1;

    public const COMMAND_CUSTOM_LED = 227;

    private const COMMAND_CUSTOM_LED_READ_LENGTH = 1;

    public const COMMAND_RGB_LED = 228;

    private const COMMAND_RGB_LED_READ_LENGTH = 9;

    public const COMMAND_ALL_LEDS = 229;

    private const COMMAND_ALL_LEDS_READ_LENGTH = 1;

    public const COMMAND_STATUS = 250;

    public const COMMAND_DATA_CHANGED = 251;

    private const POWER_LED_BIT = 7;

    private const ERROR_LED_BIT = 6;

    private const CONNECT_LED_BIT = 5;

    private const TRANSRECEIVE_LED_BIT = 4;

    private const TRANSCEIVE_LED_BIT = 3;

    private const RECEIVE_LED_BIT = 2;

    private const CUSTOM_LED_BIT = 1;

    public const RGB_LED_BIT = 0;

    public const POWER_LED_KEY = 'power';

    public const ERROR_LED_KEY = 'error';

    public const CONNECT_LED_KEY = 'connect';

    public const TRANSRECEIVE_LED_KEY = 'transreceive';

    public const TRANSCEIVE_LED_KEY = 'transceive';

    public const RECEIVE_LED_KEY = 'receive';

    public const CUSTOM_LED_KEY = 'custom';

    public const RGB_LED_KEY = 'rgb';

    abstract public function slaveHandshake(Module $module): Module;

    abstract public function onOverwriteExistingSlave(Module $module, Module $existingSlave): Module;

    abstract public function receive(Module $module, BusMessage $busMessage): void;

    /**
     * @return class-string
     */
    abstract protected function getEventClassName(): string;

    public function __construct(
        MasterService $masterService,
        TransformService $transformService,
        protected EventService $eventService,
        private ModuleRepository $moduleRepository,
        private TypeRepository $typeRepository,
        private MasterRepository $masterRepository,
        LogRepository $logRepository,
        private SlaveFactory $slaveFactory,
        LoggerInterface $logger,
        ModelManager $modelManager
    ) {
        parent::__construct($masterService, $transformService, $logRepository, $logger, $modelManager);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    public function handshake(Module $slave): Module
    {
        $master = $slave->getMaster();

        if ($master === null) {
            throw new WriteException(sprintf('Slave #%d has no master!', $slave->getId() ?? 0));
        }

        $this->logger->debug(sprintf(
            'Handshake hc slave address %d on master address %s',
            $slave->getAddress() ?? 0,
            $master->getAddress()
        ));

        $typeId = $this->readTypeId($slave);

        if ($typeId !== $slave->getTypeId()) {
            $slave->setType($this->typeRepository->getById($typeId));

            return $this->slaveFactory->get($slave->getType()->getHelper())
                ->handshake($slave)
            ;
        }

        $deviceId = $this->readDeviceId($slave);

        if (
            $deviceId !== $slave->getDeviceId() ||
            $slave->getId() === null
        ) {
            try {
                $slave = $this->moduleRepository->getByDeviceId($deviceId);
                $slave = $this->handshakeExistingSlave($slave);
            } catch (SelectError) {
                $slave->setDeviceId($deviceId);
                $slave = $this->handshakeNewSlave($slave);
            }
        } else {
            $slave = $this->handshakeExistingSlave($slave);
        }

        $slave->setMaster($master);

        $this->logger->debug(sprintf(
            'Slave address %d on master address %s has input check',
            $slave->getAddress() ?? 0,
            $master->getAddress()
        ));

        if ($slave->getType()->getHasInput()) {
            $busMessage = (new BusMessage($master->getAddress(), MasterService::TYPE_SLAVE_HAS_INPUT_CHECK))
                ->setSlaveAddress($slave->getAddress())
                ->setPort($master->getSendPort())
            ;
            $this->masterService->send($master, $busMessage);
            $this->masterService->receiveReceiveReturn($master, $busMessage);
        }

        $pwmSpeed = $this->readPwmSpeed($slave);
        $slave
            ->setHertz($this->readHertz($slave))
            ->setBufferSize($this->readBufferSize($slave))
            ->setEepromSize($this->readEepromSize($slave))
            ->setPwmSpeed($pwmSpeed === 0 ? null : $pwmSpeed)
        ;

        return $this->slaveHandshake($slave);
    }

    /**
     * @throws AbstractException
     * @throws GetError
     */
    private function checkDeviceId(Module $slave): void
    {
        if (
            $slave->getDeviceId() > 0 &&
            $slave->getDeviceId() <= self::MAX_DEVICE_ID
        ) {
            return;
        }

        $deviceId = $this->moduleRepository->getFreeDeviceId();
        $this->writeDeviceId($slave, $deviceId);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    private function handshakeNewSlave(Module $slave): Module
    {
        $master = $slave->getMaster();

        if ($master === null) {
            throw new WriteException(sprintf('Slave #%d has no master!', $slave->getId() ?? 0));
        }

        $this->checkDeviceId($slave);

        try {
            $this->typeRepository->getByDefaultAddress($slave->getAddress() ?? 0);
        } catch (SelectError) {
            return $slave;
        }

        $this->writeAddress(
            $slave,
            $this->masterRepository->getNextFreeAddress($master->getId() ?? 0)
        );

        return $slave;
    }

    /**
     * @throws AbstractException
     * @throws GetError
     */
    private function handshakeExistingSlave(Module $slave): Module
    {
        $this->checkDeviceId($slave);

        /*(new Log())
            ->setMasterId($slave->getMaster()->getId())
            ->setType(MasterService::TYPE_SLAVE_IS_HC)
            ->setData(dechex((int) $slave->getAddress()))
            ->setDirection(Log::DIRECTION_OUTPUT)
            ->save()
        ;*/

        return $slave;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function write(Module $slave, int $command, string $data = ''): void
    {
        // @todo Workaround. Server sendet bei einem Byte die Daten anders. Denk drüber nach!
        if (strlen($data) === 1) {
            $data .= 'a';
        }

        $this->writeRaw($slave, $command, $data);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function writeRaw(Module $slave, int $command, string $data = ''): void
    {
        parent::write($slave, $command, $data);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws FactoryError
     * @throws SaveError
     * @throws WriteException
     * @throws EventException
     * @throws JsonException
     * @throws ReflectionException
     */
    public function writeAddress(Module $slave, int $address): void
    {
        $master = $slave->getMaster();

        if ($master === null) {
            throw new WriteException(sprintf('Slave #%d has no master!', $slave->getId() ?? 0));
        }

        $deviceId = $slave->getDeviceId() ?? 0;

        $this->logger->debug(sprintf(
            'Write new address %d to slave with current address %d and device ID %d',
            $address,
            $slave->getAddress() ?? 0,
            $deviceId
        ));

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_ADDRESS, ['slave' => $slave, 'newAddress' => $address]);

        $this->write(
            $slave,
            self::COMMAND_ADDRESS,
            $this->getDeviceIdAsString($deviceId) . chr($address)
        );
        $this->masterService->scanBus($master);

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_ADDRESS, ['slave' => $slave, 'newAddress' => $address]);

        $slave->setAddress($address);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readDeviceId(Module $slave): int
    {
        $this->logger->debug(sprintf('Read device ID from slave %d', $slave->getAddress() ?? 0));

        $data = $this->read($slave, self::COMMAND_DEVICE_ID, self::COMMAND_DEVICE_ID_READ_LENGTH);
        $deviceId = $this->transformService->asciiToUnsignedInt($data);

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_DEVICE_ID, ['slave' => $slave, 'deviceId' => $deviceId]);

        $this->logger->debug(sprintf(
            'Device ID from slave %d is %d',
            $slave->getAddress() ?? 0,
            $deviceId
        ));

        return $deviceId;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writeDeviceId(Module $slave, int $deviceId): void
    {
        $this->logger->debug(sprintf('Write device ID %d to slave %d', $deviceId, $slave->getAddress() ?? 0));

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_DEVICE_ID, ['slave' => $slave, 'newDeviceId' => $deviceId]);

        $this->write(
            $slave,
            self::COMMAND_DEVICE_ID,
            $this->getDeviceIdAsString($slave->getDeviceId() ?? 0) .
            $this->getDeviceIdAsString($deviceId)
        );

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_DEVICE_ID, ['slave' => $slave, 'newDeviceId' => $deviceId]);

        $slave->setDeviceId($deviceId);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readTypeId(Module $slave): int
    {
        $this->logger->debug(sprintf('Read type ID from slave %d', $slave->getAddress() ?? 0));

        $data = $this->read($slave, self::COMMAND_TYPE, self::COMMAND_TYPE_READ_LENGTH);
        $typeId = $this->transformService->asciiToUnsignedInt($data, 0);

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_TYPE_ID, ['slave' => $slave, 'typeId' => $typeId]);

        return $typeId;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writeTypeId(Module $slave, Type $type): AbstractSlave
    {
        $this->logger->debug(sprintf(
            'Write type ID %d to slave %d',
            $type->getId() ?? 0,
            $slave->getAddress() ?? 0
        ));

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_TYPE_ID, ['slave' => $slave, 'typeId' => $type->getId()]);

        $this->write($slave, self::COMMAND_TYPE, chr((int) $type->getId()));

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_TYPE_ID, ['slave' => $slave, 'typeId' => $type->getId()]);

        $slave->setType($type);
        $slaveService = $this->slaveFactory->get($type->getHelper());
        $slaveService->handshake($slave);

        return $slaveService;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writeRestart(Module $slave): void
    {
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_RESTART, ['slave' => $slave]);

        $this->write(
            $slave,
            self::COMMAND_RESTART,
            $this->getDeviceIdAsString($slave->getDeviceId() ?? 0)
        );

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_RESTART, ['slave' => $slave]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writePwmSpeed(Module $slave, int $speed): void
    {
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_PWM_SPEED, ['slave' => $slave, 'pwmSpeed' => $speed]);

        $this->write(
            $slave,
            self::COMMAND_PWM_SPEED,
            chr($speed >> 8) . chr($speed)
        );

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_PWM_SPEED, ['slave' => $slave, 'pwmSpeed' => $speed]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    protected function readConfig(Module $slave, int $length): string
    {
        $config = $this->read($slave, self::COMMAND_CONFIGURATION, $length);

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_CONFIG, ['slave' => $slave, 'config' => $config]);

        return $config;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readHertz(Module $slave): int
    {
        $this->logger->debug(sprintf('Read hertz from slave %d', $slave->getAddress() ?? 0));

        $data = $this->read($slave, self::COMMAND_HERTZ, self::COMMAND_HERTZ_READ_LENGTH);
        $hertz = $this->transformService->asciiToUnsignedInt($data);

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_HERTZ, ['slave' => $slave, 'hertz' => $hertz]);

        return $hertz;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readPwmSpeed(Module $slave): int
    {
        $this->logger->debug(sprintf('Read pwm speed from slave %d', $slave->getAddress() ?? 0));

        $data = $this->read($slave, self::COMMAND_PWM_SPEED, self::COMMAND_PWM_SPEED_READ_LENGTH);
        $speed = $this->transformService->asciiToUnsignedInt($data);

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_PWM_SPEED, ['slave' => $slave, 'pwmSpeed' => $speed]);

        return $speed;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readEepromSize(Module $slave): int
    {
        $this->logger->debug(sprintf('Read eeprom size slave %d', $slave->getAddress() ?? 0));

        $data = $this->read($slave, self::COMMAND_EEPROM_SIZE, self::COMMAND_EEPROM_SIZE_READ_LENGTH);
        $eepromSize = $this->transformService->asciiToUnsignedInt($data);

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_EEPROM_SIZE, ['slave' => $slave, 'eepromSize' => $eepromSize]);

        return $eepromSize;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readEepromFree(Module $slave): int
    {
        $this->logger->debug(sprintf('Read eeprom free from slave %d', $slave->getAddress() ?? 0));

        $data = $this->read($slave, self::COMMAND_EEPROM_FREE, self::COMMAND_EEPROM_FREE_READ_LENGTH);
        $eepromFree = $this->transformService->asciiToUnsignedInt($data);

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_EEPROM_FREE, ['slave' => $slave, 'eepromFree' => $eepromFree]);

        return $eepromFree;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readEepromPosition(Module $slave): int
    {
        $this->logger->debug(sprintf('Read eeprom position from slave %d', $slave->getAddress() ?? 0));

        $data = $this->read($slave, self::COMMAND_EEPROM_POSITION, self::COMMAND_EEPROM_POSITION_READ_LENGTH);
        $eepromPosition = $this->transformService->asciiToUnsignedInt($data);

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_EEPROM_POSITION, ['slave' => $slave, 'eepromPosition' => $eepromPosition]);

        return $eepromPosition;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writeEepromPosition(Module $slave, int $position): void
    {
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_EEPROM_POSITION, ['slave' => $slave, 'eepromPosition' => $position]);

        $this->write(
            $slave,
            self::COMMAND_EEPROM_POSITION,
            chr($position >> 8) . chr($position & 255)
        );

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_EEPROM_POSITION, ['slave' => $slave, 'eepromPosition' => $position]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writeEepromErase(Module $slave): void
    {
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_EEPROM_ERASE, ['slave' => $slave]);

        $this->write(
            $slave,
            self::COMMAND_EEPROM_ERASE,
            $this->getDeviceIdAsString($slave->getDeviceId() ?? 0)
        );

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_EEPROM_ERASE, ['slave' => $slave]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readBufferSize(Module $slave): int
    {
        $this->logger->debug(sprintf('Read buffer size from slave %d', $slave->getAddress() ?? 0));

        $data = $this->read($slave, self::COMMAND_BUFFER_SIZE, self::COMMAND_BUFFER_SIZE_READ_LENGTH);
        $bufferSize = $this->transformService->asciiToUnsignedInt($data);

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_BUFFER_SIZE, ['slave' => $slave, 'bufferSize' => $bufferSize]);

        return $bufferSize;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readLedStatus(Module $slave): array
    {
        $this->logger->debug(sprintf('Read LED status from slave %d', $slave->getAddress() ?? 0));

        $leds = $this->transformService->asciiToUnsignedInt($this->read(
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
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_LED_STATUS, $eventData);

        return $ledStatus;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writePowerLed(Module $slave, bool $on): void
    {
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_POWER_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_POWER_LED, chr((int) $on));

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_POWER_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writeErrorLed(Module $slave, bool $on): void
    {
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_ERROR_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_ERROR_LED, chr((int) $on));

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_ERROR_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writeConnectLed(Module $slave, bool $on): void
    {
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_CONNECT_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_CONNECT_LED, chr((int) $on));

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_CONNECT_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writeTransreceiveLed(Module $slave, bool $on): void
    {
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_TRANSRECEIVE_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_TRANSRECEIVE_LED, chr((int) $on));

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_TRANSRECEIVE_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writeTransceiveLed(Module $slave, bool $on): void
    {
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_TRANSCEIVE_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_TRANSCEIVE_LED, chr((int) $on));

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_TRANSCEIVE_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writeReceiveLed(Module $slave, bool $on): void
    {
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_RECEIVE_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_RECEIVE_LED, chr((int) $on));

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_RECEIVE_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function writeCustomLed(Module $slave, bool $on): void
    {
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_CUSTOM_LED, ['slave' => $slave, 'on' => $on]);

        $this->write($slave, self::COMMAND_CUSTOM_LED, chr((int) $on));

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_CUSTOM_LED, ['slave' => $slave, 'on' => $on]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readPowerLed(Module $slave): bool
    {
        $on = (bool) $this->transformService->asciiToUnsignedInt(
            $this->read(
                $slave,
                self::COMMAND_POWER_LED,
                self::COMMAND_POWER_LED_READ_LENGTH
            )
        );

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_POWER_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readErrorLed(Module $slave): bool
    {
        $on = (bool) $this->transformService->asciiToUnsignedInt(
            $this->read(
                $slave,
                self::COMMAND_ERROR_LED,
                self::COMMAND_ERROR_LED_READ_LENGTH
            )
        );

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_ERROR_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readConnectLed(Module $slave): bool
    {
        $on = (bool) $this->transformService->asciiToUnsignedInt(
            $this->read(
                $slave,
                self::COMMAND_CONNECT_LED,
                self::COMMAND_CONNECT_LED_READ_LENGTH
            )
        );

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_CONNECT_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readTransreceiveLed(Module $slave): bool
    {
        $on = (bool) $this->transformService->asciiToUnsignedInt(
            $this->read(
                $slave,
                self::COMMAND_TRANSRECEIVE_LED,
                self::COMMAND_TRANSRECEIVE_LED_READ_LENGTH
            )
        );

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_TRANSRECEIVE_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readTransceiveLed(Module $slave): bool
    {
        $on = (bool) $this->transformService->asciiToUnsignedInt(
            $this->read(
                $slave,
                self::COMMAND_TRANSCEIVE_LED,
                self::COMMAND_TRANSCEIVE_LED_READ_LENGTH
            )
        );

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_TRANSCEIVE_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readReceiveLed(Module $slave): bool
    {
        $on = (bool) $this->transformService->asciiToUnsignedInt(
            $this->read(
                $slave,
                self::COMMAND_RECEIVE_LED,
                self::COMMAND_RECEIVE_LED_READ_LENGTH
            )
        );

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_RECEIVE_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readCustomLed(Module $slave): bool
    {
        $on = (bool) $this->transformService->asciiToUnsignedInt(
            $this->read(
                $slave,
                self::COMMAND_CUSTOM_LED,
                self::COMMAND_CUSTOM_LED_READ_LENGTH
            )
        );

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_CUSTOM_LED, ['slave' => $slave, 'on' => $on]);

        return $on;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
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
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_RGB_LED, $eventData);

        $power = $this->transformService->hexToInt($power);
        $error = $this->transformService->hexToInt($error);
        $connect = $this->transformService->hexToInt($connect);
        $transceive = $this->transformService->hexToInt($transceive);
        $receive = $this->transformService->hexToInt($receive);
        $custom = $this->transformService->hexToInt($custom);

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

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_RGB_LED, $eventData);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readRgbLed(Module $slave): array
    {
        $rgbLed = $this->transformService->asciiToHex($this->read(
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
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_RGB_LED, $eventData);

        return $colors;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
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
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::BEFORE_WRITE_ALL_LEDS, $eventData);
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

        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::AFTER_WRITE_ALL_LEDS, $eventData);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readAllLeds(Module $slave): array
    {
        $leds = $this->transformService->asciiToUnsignedInt(
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
        $this->eventService->fire($this->getEventClassName(), AbstractHcEvent::READ_ALL_LEDS, $eventData);

        return $leds;
    }

    protected function getDeviceIdAsString(int $deviceId): string
    {
        return chr($deviceId >> 8) . chr($deviceId & 255);
    }
}
