<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use Exception;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Event\Describer\IoDescriber as IoDescriber;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Mapper\IoMapper;
use GibsonOS\Module\Hc\Model\Attribute as AttributeModel;
use GibsonOS\Module\Hc\Model\Attribute\Value as ValueModel;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository as ValueRepository;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository as ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository as TypeRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;
use Psr\Log\LoggerInterface;
use Throwable;

class IoService extends AbstractHcSlave
{
    public const COMMAND_PORT_LENGTH = 2;

    public const COMMAND_ADD_DIRECT_CONNECT = 129;

    public const COMMAND_SET_DIRECT_CONNECT = 130;

    public const COMMAND_DELETE_DIRECT_CONNECT = 131;

    public const COMMAND_RESET_DIRECT_CONNECT = 132;

    public const COMMAND_READ_DIRECT_CONNECT = 133;

    public const COMMAND_READ_DIRECT_CONNECT_READ_LENGTH = 3;

    public const COMMAND_DEFRAGMENT_DIRECT_CONNECT = 134;

    public const COMMAND_STATUS_IN_EEPROM = 135;

    public const COMMAND_STATUS_IN_EEPROM_LENGTH = 1;

    public const COMMAND_DIRECT_CONNECT_STATUS = 136;

    public const COMMAND_DIRECT_CONNECT_STATUS_READ_LENGTH = 1;

    public const COMMAND_CONFIGURATION_READ_LENGTH = 1;

    public const PORT_BYTE_LENGTH = 2;

    public const DIRECTION_INPUT = 0;

    public const DIRECTION_OUTPUT = 1;

    public const ATTRIBUTE_TYPE_PORT = 'port';

    public const ATTRIBUTE_PORT_KEY_NAME = 'name';

    public const ATTRIBUTE_PORT_KEY_DIRECTION = 'direction';

    public const ATTRIBUTE_PORT_KEY_PULL_UP = 'pullUp';

    public const ATTRIBUTE_PORT_KEY_PWM = 'pwm';

    public const ATTRIBUTE_PORT_KEY_BLINK = 'blink';

    public const ATTRIBUTE_PORT_KEY_DELAY = 'delay';

    public const ATTRIBUTE_PORT_KEY_VALUE = 'value';

    public const ATTRIBUTE_PORT_KEY_FADE_IN = 'fade';

    public const ATTRIBUTE_PORT_KEY_VALUE_NAMES = 'valueName';

    public const ATTRIBUTE_TYPE_DIRECT_CONNECT = 'directConnect';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE = 'inputPortValue';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT = 'outputPort';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_PWM = 'pwm';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK = 'blink';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN = 'fadeIn';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE = 'value';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB = 'addOrSub';

    public const DIRECT_CONNECT_READ_NOT_SET = 127;

    public const DIRECT_CONNECT_READ_NOT_EXIST = 255;

    public const DIRECT_CONNECT_READ_RETRY = 5;

    public function __construct(
        MasterService $masterService,
        TransformService $transformService,
        EventService $eventService,
        private IoMapper $ioMapper,
        ModuleRepository $moduleRepository,
        TypeRepository $typeRepository,
        MasterRepository $masterRepository,
        LogRepository $logRepository,
        SlaveFactory $slaveFactory,
        private AttributeRepository $attributeRepository,
        private ValueRepository $valueRepository,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $masterService,
            $transformService,
            $eventService,
            $moduleRepository,
            $typeRepository,
            $masterRepository,
            $logRepository,
            $slaveFactory,
            $logger
        );
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws ReceiveError
     * @throws SaveError
     * @throws Throwable
     */
    public function slaveHandshake(Module $slave): Module
    {
        if (empty($slave->getConfig())) {
            $slave->setConfig(
                (string) $this->transformService->asciiToUnsignedInt(
                    $this->readConfig($slave, self::COMMAND_CONFIGURATION_READ_LENGTH)
                )
            );

            $slave->save();
        }

        $ports = $this->readPorts($slave);
        $this->attributeRepository->startTransaction();

        try {
            if ($this->attributeRepository->countByModule($slave, self::ATTRIBUTE_TYPE_PORT)) {
                foreach ($ports as $number => $port) {
                    $this->updatePortAttributes($slave, $number, $port);
                }
            } else {
                foreach ($ports as $number => $port) {
                    $number = (int) $number;
                    $this->attributeRepository->addByModule(
                        $slave,
                        ['IO ' . ($number + 1)],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_NAME,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    $this->attributeRepository->addByModule(
                        $slave,
                        [(string) $port[self::ATTRIBUTE_PORT_KEY_VALUE]],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_VALUE,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    $this->attributeRepository->addByModule(
                        $slave,
                        [
                            0 => $port[self::ATTRIBUTE_PORT_KEY_DIRECTION] === self::DIRECTION_INPUT ? 'Zu' : 'Aus',
                            1 => $port[self::ATTRIBUTE_PORT_KEY_DIRECTION] === self::DIRECTION_INPUT ? 'Offen' : 'An',
                        ],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_VALUE_NAMES,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    $this->attributeRepository->addByModule(
                        $slave,
                        [(string) $port[self::ATTRIBUTE_PORT_KEY_DIRECTION]],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_DIRECTION,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    $this->attributeRepository->addByModule(
                        $slave,
                        [(string) ($port[self::ATTRIBUTE_PORT_KEY_PULL_UP] ?? 0)],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_PULL_UP,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    $this->attributeRepository->addByModule(
                        $slave,
                        [(string) ($port[self::ATTRIBUTE_PORT_KEY_DELAY] ?? 0)],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_DELAY,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    $this->attributeRepository->addByModule(
                        $slave,
                        [(string) ($port[self::ATTRIBUTE_PORT_KEY_PWM] ?? 0)],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_PWM,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    $this->attributeRepository->addByModule(
                        $slave,
                        [(string) ($port[self::ATTRIBUTE_PORT_KEY_BLINK] ?? 0)],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_BLINK,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    $this->attributeRepository->addByModule(
                        $slave,
                        [(string) ($port[self::ATTRIBUTE_PORT_KEY_FADE_IN] ?? 0)],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_FADE_IN,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                }
            }
        } catch (Throwable $exception) {
            $this->attributeRepository->rollback();

            throw $exception;
        }

        $this->attributeRepository->commit();

        return $slave;
    }

    public function onOverwriteExistingSlave(Module $slave, Module $existingSlave): Module
    {
        // @todo IOs setzen
        // @todo direct connects schreiben

        return $slave;
    }

    /**
     * @throws SaveError
     */
    public function receive(Module $slave, BusMessage $busMessage): void
    {
        foreach ($this->ioMapper->getPortsAsArray($busMessage->getData() ?? '', (int) $slave->getConfig()) as $number => $port) {
            $this->updatePortAttributes($slave, $number, $port);
        }
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readPort(Module $slave, int $number): array
    {
        $eventData = ['slave' => $slave, 'number' => $number];
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::BEFORE_READ_PORT, $eventData);

        $port = $this->ioMapper->getPortAsArray($this->read($slave, $number, self::COMMAND_PORT_LENGTH));
        $this->updatePortAttributes($slave, $number, $port);

        $eventData = array_merge($eventData, $port);
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::AFTER_READ_PORT, $eventData);

        return $port;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    private function writePort(Module $slave, int $number, array $data): void
    {
        $eventData = $data;
        $eventData['slave'] = $slave;
        $eventData['number'] = $number;

        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::BEFORE_WRITE_PORT, $eventData);
        $this->write($slave, $number, $this->ioMapper->getPortAsString($data));
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::AFTER_WRITE_PORT, $eventData);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readPortsFromEeprom(Module $slave): void
    {
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::BEFORE_READ_PORTS_FROM_EEPROM, ['slave' => $slave]);

        if (empty($this->transformService->asciiToUnsignedInt($this->read(
            $slave,
            self::COMMAND_STATUS_IN_EEPROM,
            self::COMMAND_STATUS_IN_EEPROM_LENGTH
        )))) {
            throw new ReceiveError('Kein Status im EEPROM vorhanden!');
        }

        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::AFTER_READ_PORTS_FROM_EEPROM, ['slave' => $slave]);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writePortsToEeprom(Module $slave): void
    {
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::BEFORE_WRITE_PORTS_TO_EEPROM, ['slave' => $slave]);
        $this->write(
            $slave,
            self::COMMAND_STATUS_IN_EEPROM,
            $this->getDeviceIdAsString($slave->getDeviceId() ?? 0)
        );
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::AFTER_WRITE_PORTS_TO_EEPROM, ['slave' => $slave]);
    }

    /**
     * @throws Exception
     */
    private function completePortAttributes(Module $slave, int $number, array $data): array
    {
        $valueModels = $this->valueRepository->getByTypeId(
            $slave->getTypeId(),
            $number,
            [(int) $slave->getId()],
            self::ATTRIBUTE_TYPE_PORT
        );

        foreach ($valueModels as $valueModel) {
            $key = $valueModel->getAttribute()->getKey();

            if (isset($data[$key])) {
                continue;
            }

            $data[$key] = $valueModel->getValue();
        }

        return $data;
    }

    /**
     * @throws SaveError
     * @throws Exception
     */
    private function updatePortAttributes(Module $slave, int $number, array $data): bool
    {
        $valueModels = $this->valueRepository->getByTypeId(
            $slave->getTypeId(),
            $number,
            [(int) $slave->getId()],
            self::ATTRIBUTE_TYPE_PORT
        );

        $hasChanges = false;

        foreach ($valueModels as $valueModel) {
            $key = $valueModel->getAttribute()->getKey();

            if (!isset($data[$key])) {
                continue;
            }

            $value = $data[$key];

            if ($key === self::ATTRIBUTE_PORT_KEY_VALUE_NAMES) {
                $value = $value[$valueModel->getOrder()];
            }

            $value = (string) $value;

            if ($value === $valueModel->getValue()) {
                continue;
            }

            $valueModel->setValue($value);
            $valueModel->save();

            if (
                $key === self::ATTRIBUTE_PORT_KEY_VALUE_NAMES ||
                $key === self::ATTRIBUTE_PORT_KEY_NAME
            ) {
                continue;
            }

            $hasChanges = true;
        }

        return $hasChanges;
    }

    /**
     * @throws AbstractException
     * @throws Exception
     */
    public function toggleValue(Module $slave, int $number): void
    {
        $valueModels = $this->valueRepository->getByTypeId(
            $slave->getTypeId(),
            $number,
            [(int) $slave->getId()],
            self::ATTRIBUTE_TYPE_PORT
        );
        $data = [];

        $this->valueRepository->startTransaction();

        try {
            foreach ($valueModels as $valueModel) {
                $key = $valueModel->getAttribute()->getKey();

                if ($key === self::ATTRIBUTE_PORT_KEY_VALUE) {
                    $valueModel->setValue($valueModel->getValue() === '1' ? '0' : '1');
                    $valueModel->save();
                }

                $data[$key] = $valueModel->getValue();
            }

            $this->writePort($slave, $number, $data);
        } catch (AbstractException $exception) {
            $this->valueRepository->rollback();

            throw $exception;
        }

        $this->valueRepository->commit();
    }

    /**
     * @param string[] $valueNames
     *
     * @throws AbstractException
     * @throws Exception
     */
    public function setPort(
        Module $slave,
        int $number,
        string $name,
        int $direction,
        int $pullUp,
        int $delay,
        int $pwm,
        int $blink,
        int $fade,
        array $valueNames
    ): void {
        $data = [
            self::ATTRIBUTE_PORT_KEY_NAME => $name,
            self::ATTRIBUTE_PORT_KEY_DIRECTION => $direction,
            self::ATTRIBUTE_PORT_KEY_PULL_UP => $pullUp,
            self::ATTRIBUTE_PORT_KEY_DELAY => $delay,
            self::ATTRIBUTE_PORT_KEY_PWM => $pwm,
            self::ATTRIBUTE_PORT_KEY_BLINK => $blink,
            self::ATTRIBUTE_PORT_KEY_FADE_IN => $fade,
            self::ATTRIBUTE_PORT_KEY_VALUE_NAMES => $valueNames,
        ];

        if ($fade) {
            $data[self::ATTRIBUTE_PORT_KEY_VALUE] = 1;
        }

        $this->valueRepository->startTransaction();

        try {
            if ($this->updatePortAttributes($slave, $number, $data)) {
                $this->writePort($slave, $number, $this->completePortAttributes($slave, $number, $data));
            }
        } catch (AbstractException $exception) {
            $this->valueRepository->rollback();

            throw $exception;
        }

        $this->valueRepository->commit();
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    private function readPorts(Module $slave): array
    {
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::BEFORE_READ_PORTS, ['slave' => $slave]);

        $config = (int) $slave->getConfig();
        $length = ($config) * self::PORT_BYTE_LENGTH;
        $data = $this->read($slave, self::COMMAND_STATUS, $length);
        $ports = $this->ioMapper->getPortsAsArray($data, $config);

        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::AFTER_READ_PORTS, ['slave' => $slave]);

        return $ports;
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function getPorts(Module $slave): array
    {
        $ports = $this->readPorts($slave);

        foreach ($ports as $number => $port) {
            $this->updatePortAttributes($slave, $number, $port);
        }

        return $ports;
    }

    /**
     * @throws AbstractException
     * @throws Exception
     */
    public function saveDirectConnect(
        Module $slave,
        int $inputPort,
        int $inputValue,
        int $order,
        int $outputPort,
        int $outputValue,
        ?int $pwm,
        ?int $blink,
        ?int $fadeIn,
        int $addOrSub
    ): void {
        $data = [
            self::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE => $inputValue,
            self::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT => $outputPort,
            self::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE => $outputValue,
            self::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM => $pwm,
            self::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK => $blink,
            self::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN => $fadeIn,
            self::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB => $addOrSub,
        ];
        $valueModels = $this->valueRepository->getByTypeId(
            $slave->getTypeId(),
            $inputPort,
            [(int) $slave->getId()],
            self::ATTRIBUTE_TYPE_DIRECT_CONNECT,
            null,
            (string) $order
        );
        $changed = false;
        $new = false;

        $this->valueRepository->startTransaction();

        try {
            if (!count($valueModels)) {
                $new = true;
                $changed = true;

                $this->createDirectConnectAttributes($slave, $inputPort, $data, $order);
            } else {
                foreach ($valueModels as $valueModel) {
                    $value = $data[$valueModel->getAttribute()->getKey()];

                    if ($valueModel->getValue() == $value) {
                        continue;
                    }

                    $changed = true;
                    $valueModel->setValue((string) $value);
                    $valueModel->save();
                }
            }

            $eventData = $data;
            $eventData['slave'] = $this;

            if ($changed) {
                $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::BEFORE_SAVE_DIRECT_CONNECT, $eventData);
                $this->eventService->fire(
                    $this->getEventDescriberClassName(),
                    $new ? IoDescriber::BEFORE_ADD_DIRECT_CONNECT : IoDescriber::BEFORE_SET_DIRECT_CONNECT,
                    $eventData
                );

                $this->write(
                    $slave,
                    $new ? self::COMMAND_ADD_DIRECT_CONNECT : self::COMMAND_SET_DIRECT_CONNECT,
                    $this->getDeviceIdAsString($slave->getDeviceId() ?? 0) .
                    $this->ioMapper->getDirectConnectAsString(
                        $inputPort,
                        $inputValue,
                        $outputPort,
                        $outputValue,
                        $pwm ?? 0,
                        $blink ?? 0,
                        $fadeIn ?? 0,
                        $addOrSub,
                        $new ? null : $order
                    )
                );
            }
        } catch (AbstractException $exception) {
            $this->valueRepository->rollback();

            throw $exception;
        }

        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::AFTER_SAVE_DIRECT_CONNECT, $eventData);
        $this->eventService->fire(
            $this->getEventDescriberClassName(),
            $new ? IoDescriber::AFTER_ADD_DIRECT_CONNECT : IoDescriber::AFTER_SET_DIRECT_CONNECT,
            $eventData
        );

        $this->valueRepository->commit();
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     * @throws Exception
     */
    public function readDirectConnect(Module $slave, int $port, int $order): array
    {
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::BEFORE_READ_DIRECT_CONNECT, [
            'slave' => $slave,
            'port' => $port,
            'order' => $order,
        ]);

        $lastByte = 0;
        $directConnect = [];

        for ($i = 0;; ++$i) {
            $this->write($slave, self::COMMAND_READ_DIRECT_CONNECT, chr($port) . chr($order));
            $data = $this->read($slave, self::COMMAND_READ_DIRECT_CONNECT, self::COMMAND_READ_DIRECT_CONNECT_READ_LENGTH);

            $lastByte = $this->transformService->asciiToUnsignedInt($data, 2);

            if ($lastByte === self::DIRECT_CONNECT_READ_NOT_SET) {
                /** @psalm-suppress TypeDoesNotContainType */
                if ($i === self::DIRECT_CONNECT_READ_RETRY) {
                    throw new ReceiveError('Es ist kein Port gesetzt!', self::DIRECT_CONNECT_READ_NOT_SET);
                }

                continue;
            }

            if ($lastByte === self::DIRECT_CONNECT_READ_NOT_EXIST) {
                throw new ReceiveError('Es existiert kein DirectConnect Befehl!', self::DIRECT_CONNECT_READ_NOT_EXIST);
            }

            $this->attributeRepository->startTransaction();

            try {
                $directConnect = $this->ioMapper->getDirectConnectAsArray($data);
                $this->createDirectConnectAttributes($slave, $port, $directConnect, $order);
            } catch (AbstractException $exception) {
                $this->attributeRepository->rollback();

                throw $exception;
            }

            $this->attributeRepository->commit();

            break;
        }

        $directConnect['hasMore'] = (($lastByte >> 5) & 1) ? true : false;

        $eventData = $directConnect;
        $eventData['slave'] = $slave;
        $eventData['port'] = $port;
        $eventData['order'] = $order;
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::AFTER_READ_DIRECT_CONNECT, $eventData);

        return $directConnect;
    }

    /**
     * @throws SaveError
     * @throws Exception
     */
    private function createDirectConnectAttributes(Module $slave, int $port, array $data, int $order = 0): void
    {
        foreach ($data as $key => $value) {
            try {
                $attribute = $this->attributeRepository->getByModule(
                    $slave,
                    $port,
                    $key,
                    self::ATTRIBUTE_TYPE_DIRECT_CONNECT
                )[0];
            } catch (SelectError) {
                $attribute = (new AttributeModel())
                    ->setModule($slave)
                    ->setType(self::ATTRIBUTE_TYPE_DIRECT_CONNECT)
                    ->setSubId($port)
                    ->setKey($key);
                $attribute->save();
            }

            (new ValueModel())
                ->setAttribute($attribute)
                ->setValue((string) $value)
                ->setOrder($order)
                ->save();
        }
    }

    /**
     * @throws AbstractException
     */
    public function deleteDirectConnect(Module $slave, int $port, int $order): void
    {
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::BEFORE_DELETE_DIRECT_CONNECT, [
            'slave' => $slave,
            'port' => $port,
            'order' => $order,
        ]);

        $this->valueRepository->startTransaction();

        try {
            $this->valueRepository->deleteBySubId(
                $port,
                $slave->getTypeId(),
                [(int) $slave->getId()],
                self::ATTRIBUTE_TYPE_DIRECT_CONNECT,
                null,
                (string) $order
            );
            $this->valueRepository->updateOrder(
                $slave->getTypeId(),
                $order,
                -1,
                [(int) $slave->getId()],
                self::ATTRIBUTE_TYPE_DIRECT_CONNECT,
                $port
            );

            $this->write(
                $slave,
                self::COMMAND_DELETE_DIRECT_CONNECT,
                $this->getDeviceIdAsString($slave->getDeviceId() ?? 0) . chr($port) . chr($order)
            );
        } catch (AbstractException $exception) {
            $this->valueRepository->rollback();

            throw $exception;
        }

        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::AFTER_DELETE_DIRECT_CONNECT, [
            'slave' => $slave,
            'port' => $port,
            'order' => $order,
        ]);

        $this->valueRepository->commit();
    }

    /**
     * @throws AbstractException
     */
    public function resetDirectConnect(Module $slave, int $port, bool $databaseOnly = false): void
    {
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::BEFORE_RESET_DIRECT_CONNECT, [
            'slave' => $slave,
            'port' => $port,
            'databaseOnly' => $databaseOnly,
        ]);

        $this->valueRepository->startTransaction();

        try {
            $this->valueRepository->deleteBySubId(
                $port,
                $slave->getTypeId(),
                [(int) $slave->getId()],
                self::ATTRIBUTE_TYPE_DIRECT_CONNECT
            );

            if (!$databaseOnly) {
                $this->write(
                    $slave,
                    self::COMMAND_RESET_DIRECT_CONNECT,
                    $this->getDeviceIdAsString($slave->getDeviceId() ?? 0) . chr($port)
                );
            }
        } catch (AbstractException $exception) {
            $this->valueRepository->rollback();

            throw $exception;
        }

        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::AFTER_RESET_DIRECT_CONNECT, [
            'slave' => $slave,
            'port' => $port,
            'databaseOnly' => $databaseOnly,
        ]);

        $this->valueRepository->commit();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function defragmentDirectConnect(Module $slave): void
    {
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::BEFORE_DEFRAGMENT_DIRECT_CONNECT, ['slave' => $slave]);
        $this->write(
            $slave,
            self::COMMAND_DEFRAGMENT_DIRECT_CONNECT,
            $this->getDeviceIdAsString($slave->getDeviceId() ?? 0)
        );
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::AFTER_DEFRAGMENT_DIRECT_CONNECT, ['slave' => $slave]);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function activateDirectConnect(Module $slave, bool $active = true): void
    {
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::BEFORE_ACTIVATE_DIRECT_CONNECT, ['slave' => $slave, 'active' => $active]);
        $this->write($slave, self::COMMAND_DIRECT_CONNECT_STATUS, chr($active ? 1 : 0));
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::AFTER_ACTIVATE_DIRECT_CONNECT, ['slave' => $slave, 'active' => $active]);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function isDirectConnectActive(Module $slave): bool
    {
        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::BEFORE_IS_DIRECT_CONNECT_ACTIVE, ['slave' => $slave]);

        $active = $this->transformService->asciiToUnsignedInt($this->read(
            $slave,
            self::COMMAND_DIRECT_CONNECT_STATUS,
            self::COMMAND_DIRECT_CONNECT_STATUS_READ_LENGTH
        )) ? true : false;

        $this->eventService->fire($this->getEventDescriberClassName(), IoDescriber::AFTER_IS_DIRECT_CONNECT_ACTIVE, ['slave' => $slave, 'active' => $active]);

        return $active;
    }

    protected function getEventDescriberClassName(): string
    {
        return IoDescriber::class;
    }
}
