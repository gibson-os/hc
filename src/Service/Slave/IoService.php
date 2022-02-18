<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use Exception;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\EventException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\DeleteError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Dto\Io\Port;
use GibsonOS\Module\Hc\Event\IoEvent;
use GibsonOS\Module\Hc\Exception\AttributeException;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Mapper\IoMapper;
use GibsonOS\Module\Hc\Model\Attribute as AttributeModel;
use GibsonOS\Module\Hc\Model\Attribute\Value as ValueModel;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;
use JsonException;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;

class IoService extends AbstractHcSlave
{
    public const COMMAND_PORT_LENGTH = 2;

    public const COMMAND_ADD_DIRECT_CONNECT = 129;

    public const COMMAND_SET_DIRECT_CONNECT = 130;

    public const COMMAND_DELETE_DIRECT_CONNECT = 131;

    public const COMMAND_RESET_DIRECT_CONNECT = 132;

    public const COMMAND_READ_DIRECT_CONNECT = 133;

    public const COMMAND_READ_DIRECT_CONNECT_READ_LENGTH = 4;

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

    public const ATTRIBUTE_PORT_KEY_VALUE_NAMES = 'valueNames';

    public const ATTRIBUTE_TYPE_DIRECT_CONNECT = 'directConnect';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE = 'inputPortValue';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT = 'outputPort';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_PWM = 'pwm';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK = 'blink';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN = 'fadeIn';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE = 'value';

    public const ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB = 'addOrSub';

    public const DIRECT_CONNECT_READ_NOT_SET = 1;

    public const DIRECT_CONNECT_READ_NOT_EXIST = 2;

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
    public function slaveHandshake(Module $module): Module
    {
        if (empty($module->getConfig())) {
            $module->setConfig(
                (string) $this->transformService->asciiToUnsignedInt(
                    $this->readConfig($module, self::COMMAND_CONFIGURATION_READ_LENGTH)
                )
            );

            $module->save();
        }

        $ports = $this->readPorts($module);
        $this->attributeRepository->startTransaction();

        try {
            foreach ($ports as $port) {
                $this->attributeRepository->saveDto($port);
            }
        } catch (Throwable $exception) {
            $this->attributeRepository->rollback();

            throw $exception;
        }

        $this->attributeRepository->commit();

        return $module;
    }

    public function onOverwriteExistingSlave(Module $module, Module $existingSlave): Module
    {
        // @todo IOs setzen
        // @todo direct connects schreiben

        return $module;
    }

    /**
     * @throws DeleteError
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    public function receive(Module $module, BusMessage $busMessage): void
    {
        foreach ($this->ioMapper->getPorts($module, $busMessage->getData() ?? '', (int) $module->getConfig()) as $port) {
            $this->attributeRepository->saveDto($port);
        }
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws ReceiveError
     * @throws SaveError
     * @throws EventException
     * @throws FactoryError
     * @throws DeleteError
     * @throws SelectError
     * @throws AttributeException
     * @throws JsonException
     * @throws ReflectionException
     */
    public function readPort(Port $port): Port
    {
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_READ_PORT, $port->jsonSerialize());
        $port = $this->ioMapper->getPort(
            $port,
            $this->read($port->getModule(), $port->getNumber(), self::COMMAND_PORT_LENGTH)
        );
        $this->attributeRepository->saveDto($port);
        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_READ_PORT, $port->jsonSerialize());

        return $port;
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
    private function writePort(Port $port): void
    {
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_WRITE_PORT, $port->jsonSerialize());
        $this->write($port->getModule(), $port->getNumber(), $this->ioMapper->getPortAsString($port));
        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_WRITE_PORT, $port->jsonSerialize());
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
    public function readPortsFromEeprom(Module $slave): void
    {
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_READ_PORTS_FROM_EEPROM, ['slave' => $slave]);

        if (empty($this->transformService->asciiToUnsignedInt($this->read(
            $slave,
            self::COMMAND_STATUS_IN_EEPROM,
            self::COMMAND_STATUS_IN_EEPROM_LENGTH
        )))) {
            throw new ReceiveError('Kein Status im EEPROM vorhanden!');
        }

        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_READ_PORTS_FROM_EEPROM, ['slave' => $slave]);
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
    public function writePortsToEeprom(Module $slave): void
    {
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_WRITE_PORTS_TO_EEPROM, ['slave' => $slave]);
        $this->write(
            $slave,
            self::COMMAND_STATUS_IN_EEPROM,
            $this->getDeviceIdAsString($slave->getDeviceId() ?? 0)
        );
        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_WRITE_PORTS_TO_EEPROM, ['slave' => $slave]);
    }

    /**
     * @throws AbstractException
     * @throws Exception
     */
    public function toggleValue(Port $port): void
    {
        $this->attributeRepository->startTransaction();

        try {
            $port->setValue(!$port->isValue());
            $this->attributeRepository->saveDto($port);
            $this->writePort($port);
        } catch (AbstractException $exception) {
            $this->attributeRepository->rollback();

            throw $exception;
        }

        $this->attributeRepository->commit();
    }

    /**
     * @param string[] $valueNames
     *
     * @throws AbstractException
     * @throws Exception
     */
    public function setPort(Port $port): void
    {
        $this->attributeRepository->startTransaction();

        try {
            $this->attributeRepository->saveDto($port);
            $this->writePort($port);
        } catch (Exception $exception) {
            $this->attributeRepository->rollback();

            throw $exception;
        }

        $this->attributeRepository->commit();
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
     * @throws SelectError
     *
     * @return Port[]
     */
    private function readPorts(Module $module): array
    {
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_READ_PORTS, ['slave' => $module]);

        $config = (int) $module->getConfig();
        $length = $config * self::PORT_BYTE_LENGTH;
        $data = $this->read($module, self::COMMAND_STATUS, $length);
        $ports = $this->ioMapper->getPorts($module, $data, $config);

        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_READ_PORTS, ['slave' => $module]);

        return $ports;
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
     * @throws SelectError
     */
    public function getPorts(Module $module): array
    {
        $ports = $this->readPorts($module);

        foreach ($ports as $port) {
            $this->attributeRepository->saveDto($port);
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
                $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_SAVE_DIRECT_CONNECT, $eventData);
                $this->eventService->fire(
                    $this->getEventClassName(),
                    $new ? IoEvent::BEFORE_ADD_DIRECT_CONNECT : IoEvent::BEFORE_SET_DIRECT_CONNECT,
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

        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_SAVE_DIRECT_CONNECT, $eventData);
        $this->eventService->fire(
            $this->getEventClassName(),
            $new ? IoEvent::AFTER_ADD_DIRECT_CONNECT : IoEvent::AFTER_SET_DIRECT_CONNECT,
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
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_READ_DIRECT_CONNECT, [
            'slave' => $slave,
            'port' => $port,
            'order' => $order,
        ]);

        $directConnect = ['hasMore' => false];

        for ($i = 0;; ++$i) {
            $this->write($slave, self::COMMAND_READ_DIRECT_CONNECT, chr($port) . chr($order));
            $data = $this->read($slave, self::COMMAND_READ_DIRECT_CONNECT, self::COMMAND_READ_DIRECT_CONNECT_READ_LENGTH);
            $lastByte = $this->transformService->asciiToUnsignedInt($data, 3);

            if ($lastByte === self::DIRECT_CONNECT_READ_NOT_SET) {
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
                $directConnect['hasMore'] = $lastByte === 255;
                $this->createDirectConnectAttributes($slave, $port, $directConnect, $order);
            } catch (AbstractException $exception) {
                $this->attributeRepository->rollback();

                throw $exception;
            }

            $this->attributeRepository->commit();
            $directConnect['hasMore'] = (bool) (($this->transformService->asciiToUnsignedInt($data, 3) >> 5) & 1);

            break;
        }

        $eventData = $directConnect;
        $eventData['slave'] = $slave;
        $eventData['port'] = $port;
        $eventData['order'] = $order;
        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_READ_DIRECT_CONNECT, $eventData);

        return $directConnect;
    }

    /**
     * @throws SaveError
     * @throws Exception
     */
    private function createDirectConnectAttributes(Module $slave, int $port, array $data, int $order = 0): void
    {
        foreach ($data as $key => $value) {
            $attributes = $this->attributeRepository->getByModule(
                $slave,
                $port,
                $key,
                self::ATTRIBUTE_TYPE_DIRECT_CONNECT
            );

            if (!isset($attributes[0])) {
                $attribute = (new AttributeModel())
                    ->setModule($slave)
                    ->setType(self::ATTRIBUTE_TYPE_DIRECT_CONNECT)
                    ->setSubId($port)
                    ->setKey($key);
                $attribute->save();
            } else {
                $attribute = $attributes[0];
            }

            (new ValueModel())
                ->setAttribute($attribute)
                ->setValue((string) $value)
                ->setOrder($order)
                ->save()
            ;
        }
    }

    /**
     * @throws AbstractException
     */
    public function deleteDirectConnect(Module $slave, int $port, int $order): void
    {
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_DELETE_DIRECT_CONNECT, [
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

        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_DELETE_DIRECT_CONNECT, [
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
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_RESET_DIRECT_CONNECT, [
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

        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_RESET_DIRECT_CONNECT, [
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
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_DEFRAGMENT_DIRECT_CONNECT, ['slave' => $slave]);
        $this->write(
            $slave,
            self::COMMAND_DEFRAGMENT_DIRECT_CONNECT,
            $this->getDeviceIdAsString($slave->getDeviceId() ?? 0)
        );
        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_DEFRAGMENT_DIRECT_CONNECT, ['slave' => $slave]);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function activateDirectConnect(Module $slave, bool $active = true): void
    {
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_ACTIVATE_DIRECT_CONNECT, ['slave' => $slave, 'active' => $active]);
        $this->write($slave, self::COMMAND_DIRECT_CONNECT_STATUS, chr($active ? 1 : 0));
        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_ACTIVATE_DIRECT_CONNECT, ['slave' => $slave, 'active' => $active]);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function isDirectConnectActive(Module $slave): bool
    {
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_IS_DIRECT_CONNECT_ACTIVE, ['slave' => $slave]);

        $active = $this->transformService->asciiToUnsignedInt($this->read(
            $slave,
            self::COMMAND_DIRECT_CONNECT_STATUS,
            self::COMMAND_DIRECT_CONNECT_STATUS_READ_LENGTH
        )) ? true : false;

        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_IS_DIRECT_CONNECT_ACTIVE, ['slave' => $slave, 'active' => $active]);

        return $active;
    }

    protected function getEventClassName(): string
    {
        return IoEvent::class;
    }
}
