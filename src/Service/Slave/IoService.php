<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use Exception;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Formatter\IoFormatter;
use GibsonOS\Module\Hc\Model\Attribute as AttributeModel;
use GibsonOS\Module\Hc\Model\Attribute\Value as ValueModel;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute as AttributeRepository;
use GibsonOS\Module\Hc\Repository\Attribute\Value as ValueRepository;
use GibsonOS\Module\Hc\Service\Event\Describer\IoService as IoDescriber;
use GibsonOS\Module\Hc\Service\EventService;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;

class IoService extends AbstractHcSlave
{
    const COMMAND_PORT_LENGTH = 2;

    const COMMAND_ADD_DIRECT_CONNECT = 129;

    const COMMAND_SET_DIRECT_CONNECT = 130;

    const COMMAND_DELETE_DIRECT_CONNECT = 131;

    const COMMAND_RESET_DIRECT_CONNECT = 132;

    const COMMAND_READ_DIRECT_CONNECT = 133;

    const COMMAND_READ_DIRECT_CONNECT_READ_LENGTH = 3;

    const COMMAND_DEFRAGMENT_DIRECT_CONNECT = 134;

    const COMMAND_STATUS_IN_EEPROM = 135;

    const COMMAND_STATUS_IN_EEPROM_LENGTH = 1;

    const COMMAND_DIRECT_CONNECT_STATUS = 136;

    const COMMAND_DIRECT_CONNECT_STATUS_READ_LENGTH = 1;

    const COMMAND_CONFIGURATION_READ_LENGTH = 1;

    const PORT_BYTE_LENGTH = 2;

    const DIRECTION_INPUT = 0;

    const DIRECTION_OUTPUT = 1;

    const ATTRIBUTE_TYPE_PORT = 'port';

    const ATTRIBUTE_PORT_KEY_NAME = 'name';

    const ATTRIBUTE_PORT_KEY_DIRECTION = 'direction';

    const ATTRIBUTE_PORT_KEY_PULL_UP = 'pullUp';

    const ATTRIBUTE_PORT_KEY_PWM = 'pwm';

    const ATTRIBUTE_PORT_KEY_BLINK = 'blink';

    const ATTRIBUTE_PORT_KEY_DELAY = 'delay';

    const ATTRIBUTE_PORT_KEY_VALUE = 'value';

    const ATTRIBUTE_PORT_KEY_FADE_IN = 'fade';

    const ATTRIBUTE_PORT_KEY_VALUE_NAMES = 'valueName';

    const ATTRIBUTE_TYPE_DIRECT_CONNECT = 'directConnect';

    const ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE = 'inputPortValue';

    const ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT = 'outputPort';

    const ATTRIBUTE_DIRECT_CONNECT_KEY_PWM = 'pwm';

    const ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK = 'blink';

    const ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN = 'fadeIn';

    const ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE = 'value';

    const ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB = 'addOrSub';

    const DIRECT_CONNECT_READ_NOT_SET = 127;

    const DIRECT_CONNECT_READ_NOT_EXIST = 255;

    const DIRECT_CONNECT_READ_RETRY = 5;

    /**
     * @var IoFormatter
     */
    private $formatter;

    public function __construct(
        MasterService $master,
        EventService $event,
        TransformService $transform,
        IoFormatter $formatter,
        array $attributes = []
    ) {
        parent::__construct($master, $event, $transform, $attributes);
        $this->formatter = $formatter;
    }

    /**
     * @param Module $slave
     *
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     * @throws GetError
     * @throws Exception
     * @throws AbstractException
     *
     * @return Module
     */
    public function handshake(Module $slave): Module
    {
        parent::handshake($slave);

        if ($slave->getConfig() === null) {
            $slave->setConfig(
                (string) $this->transform->asciiToInt(
                    $this->readConfig($slave, self::COMMAND_CONFIGURATION_READ_LENGTH)
                )
            );

            $slave->save();
        }

        $ports = $this->readPorts($slave);
        AttributeRepository::startTransaction();

        try {
            if (AttributeRepository::countByModule($slave, self::ATTRIBUTE_TYPE_PORT)) {
                foreach ($ports as $number => $port) {
                    $this->updatePortAttributes($slave, $number, $port);
                }
            } else {
                foreach ($ports as $number => $port) {
                    AttributeRepository::addByModule(
                        $slave,
                        ['IO ' . ($number + 1)],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_NAME,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    AttributeRepository::addByModule(
                        $slave,
                        [$port[self::ATTRIBUTE_PORT_KEY_VALUE]],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_VALUE,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    AttributeRepository::addByModule(
                        $slave,
                        [
                            0 => 'Geöffnet',
                            1 => 'Geschlossen',
                        ],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_VALUE_NAMES,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    AttributeRepository::addByModule(
                        $slave,
                        [$port[self::ATTRIBUTE_PORT_KEY_DIRECTION]],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_DIRECTION,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    AttributeRepository::addByModule(
                        $slave,
                        [isset($port[self::ATTRIBUTE_PORT_KEY_PULL_UP]) ? $port[self::ATTRIBUTE_PORT_KEY_PULL_UP] : 0],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_PULL_UP,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    AttributeRepository::addByModule(
                        $slave,
                        [isset($port[self::ATTRIBUTE_PORT_KEY_DELAY]) ? $port[self::ATTRIBUTE_PORT_KEY_DELAY] : 0],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_DELAY,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    AttributeRepository::addByModule(
                        $slave,
                        [isset($port[self::ATTRIBUTE_PORT_KEY_PWM]) ? $port[self::ATTRIBUTE_PORT_KEY_PWM] : 0],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_PWM,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    AttributeRepository::addByModule(
                        $slave,
                        [isset($port[self::ATTRIBUTE_PORT_KEY_BLINK]) ? $port[self::ATTRIBUTE_PORT_KEY_BLINK] : 0],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_BLINK,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                    AttributeRepository::addByModule(
                        $slave,
                        [isset($port[self::ATTRIBUTE_PORT_KEY_FADE_IN]) ? $port[self::ATTRIBUTE_PORT_KEY_FADE_IN] : 0],
                        $number,
                        self::ATTRIBUTE_PORT_KEY_FADE_IN,
                        self::ATTRIBUTE_TYPE_PORT
                    );
                }
            }
        } catch (AbstractException $exception) {
            AttributeRepository::rollback();

            throw $exception;
        }

        AttributeRepository::commit();

        return $slave;
    }

    /**
     * @param Module $slave
     * @param Module $existingSlave
     *
     * @return Module
     */
    public function onOverwriteExistingSlave(Module $slave, Module $existingSlave): Module
    {
        // @todo IOs setzen
        // @todo direct connects schreiben

        return $slave;
    }

    /**
     * @param Module      $slave
     * @param int         $type
     * @param int         $command
     * @param string|null $data
     *
     * @throws SaveError
     */
    public function receive(Module $slave, int $type, int $command, string $data = null): void
    {
        parent::receive($slave, $type, $command, $data);

        foreach ($this->formatter->getPortsAsArray($data, (int) $slave->getConfig()) as $number => $port) {
            $this->updatePortAttributes($slave, $number, $port);
        }
    }

    /**
     * @param Module $slave
     * @param int    $number
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return array
     */
    public function readPort(Module $slave, int $number): array
    {
        $eventData = ['slave' => $slave, 'number' => $number];
        $this->event->fire(IoDescriber::BEFORE_READ_PORT, $eventData);

        $port = $this->formatter->getPortAsArray($this->read($slave, $number, self::COMMAND_PORT_LENGTH));
        $this->updatePortAttributes($slave, $number, $port);

        $eventData = array_merge($eventData, $port);
        $this->event->fire(IoDescriber::AFTER_READ_PORT, $eventData);

        return $port;
    }

    /**
     * @param Module $slave
     * @param int    $number
     * @param array  $data
     *
     * @throws AbstractException
     * @throws SaveError
     */
    private function writePort(Module $slave, int $number, array $data): void
    {
        $eventData = $data;
        $eventData['slave'] = $slave;
        $eventData['number'] = $number;

        $this->event->fire(IoDescriber::BEFORE_WRITE_PORT, $eventData);
        $this->write($slave, $number, $this->formatter->getPortAsString($data));
        $this->event->fire(IoDescriber::AFTER_WRITE_PORT, $eventData);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readPortsFromEeprom(Module $slave): void
    {
        $this->event->fire(IoDescriber::BEFORE_READ_PORTS_FROM_EEPROM, ['slave' => $slave]);

        if (!$this->transform->asciiToInt($this->read($slave, self::COMMAND_STATUS_IN_EEPROM, self::COMMAND_STATUS_IN_EEPROM_LENGTH))) {
            throw new ReceiveError('Kein Status im EEPROM vorhanden!');
        }

        $this->event->fire(IoDescriber::AFTER_READ_PORTS_FROM_EEPROM, ['slave' => $slave]);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writePortsToEeprom(Module $slave): void
    {
        $this->event->fire(IoDescriber::BEFORE_WRITE_PORTS_FROM_EEPROM, ['slave' => $slave]);
        $this->write($slave, self::COMMAND_STATUS_IN_EEPROM, 'a');
        $this->event->fire(IoDescriber::AFTER_WRITE_PORTS_FROM_EEPROM, ['slave' => $slave]);
    }

    /**
     * @param Module $slave
     * @param int    $number
     * @param array  $data
     *
     * @throws Exception
     *
     * @return array
     */
    private function completePortAttributes(Module $slave, int $number, array $data): array
    {
        $valueModels = ValueRepository::getByTypeId(
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
     * @param Module $slave
     * @param int    $number
     * @param array  $data
     *
     * @throws DateTimeError
     * @throws SaveError
     *
     * @return bool
     */
    private function updatePortAttributes(Module $slave, int $number, array $data): bool
    {
        $valueModels = ValueRepository::getByTypeId(
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

            if ($key == self::ATTRIBUTE_PORT_KEY_VALUE_NAMES) {
                $value = $value[$valueModel->getOrder()];
            }

            if ($value == $valueModel->getValue()) {
                continue;
            }

            $valueModel->setValue($value);
            $valueModel->save();

            if (
                $key == self::ATTRIBUTE_PORT_KEY_VALUE_NAMES ||
                $key == self::ATTRIBUTE_PORT_KEY_NAME
            ) {
                continue;
            }

            $hasChanges = true;
        }

        return $hasChanges;
    }

    /**
     * @param Module $slave
     * @param int    $number
     *
     * @throws AbstractException
     * @throws Exception
     */
    public function toggleValue(Module $slave, int $number): void
    {
        $valueModels = ValueRepository::getByTypeId(
            $slave->getTypeId(),
            $number,
            [(int) $slave->getId()],
            self::ATTRIBUTE_TYPE_PORT
        );
        $data = [];

        ValueRepository::startTransaction();

        try {
            foreach ($valueModels as $valueModel) {
                if ($valueModel->getAttribute()->getKey() == self::ATTRIBUTE_PORT_KEY_VALUE) {
                    $valueModel->setValue($valueModel->getValue() ? '0' : '1');
                    $valueModel->save();
                }

                $data[$valueModel->getAttribute()->getKey()] = $valueModel->getValue();
            }

            $this->writePort($slave, $number, $data);
        } catch (AbstractException $exception) {
            ValueRepository::rollback();

            throw $exception;
        }

        ValueRepository::commit();
    }

    /**
     * @param Module   $slave
     * @param int      $number
     * @param string   $name
     * @param int      $direction
     * @param int      $pullUp
     * @param int      $delay
     * @param int      $pwm
     * @param int      $blink
     * @param int      $fade
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

        ValueRepository::startTransaction();

        try {
            if ($this->updatePortAttributes($slave, $number, $data)) {
                $this->writePort($slave, $number, $this->completePortAttributes($slave, $number, $data));
            }
        } catch (AbstractException $exception) {
            ValueRepository::rollback();

            throw $exception;
        }

        ValueRepository::commit();
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
    private function readPorts(Module $slave): array
    {
        $this->event->fire(IoDescriber::BEFORE_READ_PORTS, ['slave' => $slave]);

        $length = $slave->getConfig() * self::PORT_BYTE_LENGTH;
        $data = $this->read($slave, self::COMMAND_STATUS, $length);
        $ports = $this->formatter->getPortsAsArray($data, (int) $slave->getConfig());

        $this->event->fire(IoDescriber::AFTER_READ_PORTS, ['slave' => $slave]);

        return $ports;
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
    public function getPorts(Module $slave): array
    {
        $ports = $this->readPorts($slave);

        foreach ($ports as $number => $port) {
            $this->updatePortAttributes($slave, $number, $port);
        }

        return $ports;
    }

    /**
     * @param Module   $slave
     * @param int      $inputPort
     * @param int      $inputValue
     * @param int      $order
     * @param int      $outputPort
     * @param int      $outputValue
     * @param int|null $pwm
     * @param int|null $blink
     * @param int|null $fadeIn
     * @param int      $addOrSub
     *
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
        $valueModels = ValueRepository::getByTypeId(
            $slave->getTypeId(),
            $inputPort,
            [(int) $slave->getId()],
            self::ATTRIBUTE_TYPE_DIRECT_CONNECT,
            null,
            $order
        );
        $changed = false;
        $new = false;

        ValueRepository::startTransaction();

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
                    $valueModel->setValue($value);
                    $valueModel->save();
                }
            }

            $eventData = $data;
            $eventData['slave'] = $this;

            if ($changed) {
                $this->event->fire(IoDescriber::BEFORE_SAVE_DIRECT_CONNECT, $eventData);
                $this->event->fire(
                    $new ? IoDescriber::BEFORE_ADD_DIRECT_CONNECT : IoDescriber::BEFORE_SET_DIRECT_CONNECT,
                    $eventData
                );

                $this->write(
                    $slave,
                    $new ? self::COMMAND_ADD_DIRECT_CONNECT : self::COMMAND_SET_DIRECT_CONNECT,
                    $this->formatter->getDirectConnectAsString(
                        $inputPort,
                        $inputValue,
                        $outputPort,
                        $outputValue,
                        $pwm,
                        $blink,
                        $fadeIn,
                        $addOrSub,
                        $new ? null : $order
                    )
                );
            }
        } catch (AbstractException $exception) {
            ValueRepository::rollback();

            throw $exception;
        }

        $this->event->fire(IoDescriber::AFTER_SAVE_DIRECT_CONNECT, $eventData);
        $this->event->fire(
            $new ? IoDescriber::AFTER_ADD_DIRECT_CONNECT : IoDescriber::AFTER_SET_DIRECT_CONNECT,
            $eventData
        );

        ValueRepository::commit();
    }

    /**
     * @param Module $slave
     * @param int    $port
     * @param int    $order
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return array
     */
    public function readDirectConnect(Module $slave, int $port, int $order): array
    {
        $this->event->fire(IoDescriber::BEFORE_READ_DIRECT_CONNECT, [
            'slave' => $this,
            'port' => $port,
            'order' => $order,
        ]);

        $lastByte = 0;

        for ($i = 0;; ++$i) {
            $this->write($slave, self::COMMAND_READ_DIRECT_CONNECT, chr($port) . chr($order));
            $data = $this->read($slave, self::COMMAND_READ_DIRECT_CONNECT, self::COMMAND_READ_DIRECT_CONNECT_READ_LENGTH);

            $lastByte = $this->transform->asciiToInt($data, 2);

            if ($lastByte == self::DIRECT_CONNECT_READ_NOT_SET) {
                if ($i == self::DIRECT_CONNECT_READ_RETRY) {
                    throw new ReceiveError('Es ist kein Port gesetzt!', self::DIRECT_CONNECT_READ_NOT_SET);
                }

                continue;
            }
            if ($lastByte == self::DIRECT_CONNECT_READ_NOT_EXIST) {
                throw new ReceiveError('Es existiert kein DirectConnect Befehl!', self::DIRECT_CONNECT_READ_NOT_EXIST);
            }

            AttributeRepository::startTransaction();

            try {
                $directConnect = $this->formatter->getDirectConnectAsArray($data);
                $this->createDirectConnectAttributes($slave, $port, $directConnect, $order);
            } catch (AbstractException $exception) {
                AttributeRepository::rollback();

                throw $exception;
            }

            AttributeRepository::commit();

            break;
        }

        $directConnect['hasMore'] = (($lastByte >> 5) & 1) ? true : false;

        $eventData = $directConnect;
        $eventData['slave'] = $this;
        $eventData['port'] = $port;
        $eventData['order'] = $order;
        $this->event->fire(IoDescriber::AFTER_READ_DIRECT_CONNECT, $eventData);

        return $directConnect;
    }

    /**
     * @param Module $slave
     * @param int    $port
     * @param array  $data
     * @param int    $order
     *
     * @throws SaveError
     * @throws SelectError
     * @throws DateTimeError
     */
    private function createDirectConnectAttributes(Module $slave, int $port, array $data, int $order = 0): void
    {
        foreach ($data as $key => $value) {
            $attributes = AttributeRepository::getByModule($slave, $port, $key, self::ATTRIBUTE_TYPE_DIRECT_CONNECT);

            if (count($attributes)) {
                $attribute = $attributes[0];
            } else {
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
     * @param Module $slave
     * @param int    $port
     * @param int    $order
     *
     * @throws AbstractException
     */
    public function deleteDirectConnect(Module $slave, int $port, int $order): void
    {
        $this->event->fire(IoDescriber::BEFORE_DELETE_DIRECT_CONNECT, [
            'slave' => $slave,
            'port' => $port,
            'order' => $order,
        ]);

        ValueRepository::startTransaction();

        try {
            ValueRepository::deleteBySubId(
                $port,
                $slave->getTypeId(),
                [(int) $slave->getId()],
                self::ATTRIBUTE_TYPE_DIRECT_CONNECT,
                null,
                $order
            );
            ValueRepository::updateOrder(
                $slave->getTypeId(),
                $order,
                -1,
                [(int) $slave->getId()],
                self::ATTRIBUTE_TYPE_DIRECT_CONNECT,
                $port
            );

            $this->write($slave, self::COMMAND_DELETE_DIRECT_CONNECT, chr($port) . chr($order));
        } catch (AbstractException $exception) {
            ValueRepository::rollback();

            throw $exception;
        }

        $this->event->fire(IoDescriber::AFTER_DELETE_DIRECT_CONNECT, [
            'slave' => $slave,
            'port' => $port,
            'order' => $order,
        ]);

        ValueRepository::commit();
    }

    /**
     * @param Module $slave
     * @param int    $port
     * @param bool   $databaseOnly
     *
     * @throws AbstractException
     */
    public function resetDirectConnect(Module $slave, int $port, bool $databaseOnly = false): void
    {
        $this->event->fire(IoDescriber::BEFORE_RESET_DIRECT_CONNECT, [
            'slave' => $slave,
            'port' => $port,
            'databaseOnly' => $databaseOnly,
        ]);

        ValueRepository::startTransaction();

        try {
            ValueRepository::deleteBySubId(
                $port,
                $slave->getTypeId(),
                [(int) $slave->getId()],
                self::ATTRIBUTE_TYPE_DIRECT_CONNECT
            );

            if (!$databaseOnly) {
                $this->write($slave, self::COMMAND_RESET_DIRECT_CONNECT, chr($port));
            }
        } catch (AbstractException $exception) {
            ValueRepository::rollback();

            throw $exception;
        }

        $this->event->fire(IoDescriber::AFTER_RESET_DIRECT_CONNECT, [
            'slave' => $slave,
            'port' => $port,
            'databaseOnly' => $databaseOnly,
        ]);

        ValueRepository::commit();
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function defragmentDirectConnect(Module $slave): void
    {
        $this->event->fire(IoDescriber::BEFORE_DEFRAGMENT_DIRECT_CONNECT, ['slave' => $slave]);
        $this->write($slave, self::COMMAND_DEFRAGMENT_DIRECT_CONNECT, 'a');
        $this->event->fire(IoDescriber::AFTER_DEFRAGMENT_DIRECT_CONNECT, ['slave' => $slave]);
    }

    /**
     * @param Module $slave
     * @param bool   $active
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function activateDirectConnect(Module $slave, bool $active = true): void
    {
        $this->event->fire(IoDescriber::BEFORE_ACTIVATE_DIRECT_CONNECT, ['slave' => $slave, 'active' => $active]);
        $this->write($slave, self::COMMAND_DIRECT_CONNECT_STATUS, chr($active ? 1 : 0));
        $this->event->fire(IoDescriber::AFTER_ACTIVATE_DIRECT_CONNECT, ['slave' => $slave, 'active' => $active]);
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
    public function isDirectConnectActive(Module $slave): bool
    {
        $this->event->fire(IoDescriber::BEFORE_IS_DIRECT_CONNECT_ACTIVE, ['slave' => $slave]);

        $active = $this->transform->asciiToInt($this->read(
            $slave,
            self::COMMAND_DIRECT_CONNECT_STATUS,
            self::COMMAND_DIRECT_CONNECT_STATUS_READ_LENGTH
        )) ? true : false;

        $this->event->fire(IoDescriber::AFTER_IS_DIRECT_CONNECT_ACTIVE, ['slave' => $slave, 'active' => $active]);

        return $active;
    }
}
