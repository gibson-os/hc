<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use Exception;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\EventException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError as RepositoryDeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Repository\UpdateError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\DevicePushService;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Dto\Io\DirectConnect as DirectConnectDto;
use GibsonOS\Module\Hc\Event\IoEvent;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Mapper\Io\DirectConnectMapper;
use GibsonOS\Module\Hc\Mapper\Io\PortMapper;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Io\DirectConnectRepository;
use GibsonOS\Module\Hc\Repository\Io\PortRepository;
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

    public const ATTRIBUTE_TYPE_PORT = 'port';

    public const ATTRIBUTE_PORT_KEY_NAME = 'name';

    public const ATTRIBUTE_PORT_KEY_DIRECTION = 'direction';

    public const ATTRIBUTE_PORT_KEY_PULL_UP = 'pullUp';

    public const ATTRIBUTE_PORT_KEY_PWM = 'pwm';

    public const ATTRIBUTE_PORT_KEY_BLINK = 'blink';

    public const ATTRIBUTE_PORT_KEY_DELAY = 'delay';

    public const ATTRIBUTE_PORT_KEY_VALUE = 'value';

    public const ATTRIBUTE_PORT_KEY_FADE_IN = 'fade';

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
        ModuleRepository $moduleRepository,
        TypeRepository $typeRepository,
        MasterRepository $masterRepository,
        LogRepository $logRepository,
        SlaveFactory $slaveFactory,
        LoggerInterface $logger,
        ModelManager $modelManager,
        private readonly PortMapper $ioMapper,
        private readonly DirectConnectMapper $directConnectMapper,
        private readonly DevicePushService $devicePushService,
        private readonly PortRepository $portRepository,
        private readonly DirectConnectRepository $directConnectRepository,
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
            $logger,
            $modelManager
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

            $this->modelManager->save($module);
        }

        $ports = $this->readPorts($module);
        $this->portRepository->startTransaction();

        try {
            foreach ($ports as $port) {
                $this->modelManager->save($port);
            }
        } catch (Throwable $exception) {
            $this->portRepository->rollback();

            throw $exception;
        }

        $this->portRepository->commit();

        return $module;
    }

    public function onOverwriteExistingSlave(Module $module, Module $existingSlave): Module
    {
        // @todo IOs setzen
        // @todo direct connects schreiben

        return $module;
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    public function receive(Module $module, BusMessage $busMessage): void
    {
        $ports = $this->ioMapper->getPorts($module, $busMessage->getData() ?? '');

        foreach ($ports as $port) {
            $eventParameters = $port->jsonSerialize();
            $eventParameters['port'] = $port;
            $eventParameters['module'] = $port->getModule();

            $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_READ_PORT, $eventParameters);
            $this->modelManager->save($port);
            $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_READ_PORT, $eventParameters);
        }

        $this->pushUpdate($module, $ports);
    }

    /**
     * @param Port[] $ports
     */
    public function pushUpdate(Module $module, array $ports): void
    {
        $this->devicePushService->push(
            'hc',
            'io',
            'index',
            (string) $module->getId(),
            $ports
        );
    }

    /**
     * @throws AbstractException
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    public function readPort(Port $port): Port
    {
        $eventParameters = $port->jsonSerialize();
        $eventParameters['port'] = $port;
        $eventParameters['module'] = $port->getModule();
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_READ_PORT, $eventParameters);
        $port = $this->ioMapper->getPort(
            $port,
            $this->read($port->getModule(), $port->getNumber(), self::COMMAND_PORT_LENGTH)
        );
        $this->modelManager->save($port);
        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_READ_PORT, $eventParameters);

        return $port;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws SaveError
     * @throws WriteException
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
     * @throws ReceiveError
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
     * @throws SaveError
     * @throws WriteException
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
        $this->portRepository->startTransaction();

        try {
            $port->setValue(!$port->isValue());
            $this->modelManager->save($port);
            $this->writePort($port);
        } catch (AbstractException $exception) {
            $this->portRepository->rollback();

            throw $exception;
        }

        $this->portRepository->commit();
    }

    /**
     * @param string[] $valueNames
     *
     * @throws AbstractException
     * @throws Exception
     */
    public function setPort(Port $port): void
    {
        $this->portRepository->startTransaction();

        if ($port->getFadeIn() > 0) {
            $port->setValue(true);
        }

        try {
            $this->modelManager->save($port);
            $this->writePort($port);
        } catch (Exception $exception) {
            $this->portRepository->rollback();

            throw $exception;
        }

        $this->portRepository->commit();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws ReceiveError
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
        $ports = $this->ioMapper->getPorts($module, $data);

        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_READ_PORTS, ['slave' => $module]);

        return $ports;
    }

    /**
     * @throws AbstractException
     * @throws Exception
     */
    public function saveDirectConnect(Module $module, DirectConnect $directConnect): void
    {
        $this->directConnectRepository->startTransaction();

        try {
            $new = $directConnect->getId() === 0;
            $this->modelManager->save($directConnect);

            $eventData = $directConnect->jsonSerialize();
            $eventData['slave'] = $this;

            $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_SAVE_DIRECT_CONNECT, $eventData);
            $this->eventService->fire(
                $this->getEventClassName(),
                $new ? IoEvent::BEFORE_ADD_DIRECT_CONNECT : IoEvent::BEFORE_SET_DIRECT_CONNECT,
                $eventData
            );

            $this->write(
                $module,
                $new ? self::COMMAND_ADD_DIRECT_CONNECT : self::COMMAND_SET_DIRECT_CONNECT,
                $this->getDeviceIdAsString($module->getDeviceId() ?? 0) .
                $this->directConnectMapper->getDirectConnectAsString($directConnect, $new)
            );
        } catch (AbstractException $exception) {
            $this->directConnectRepository->rollback();

            throw $exception;
        }

        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_SAVE_DIRECT_CONNECT, $eventData);
        $this->eventService->fire(
            $this->getEventClassName(),
            $new ? IoEvent::AFTER_ADD_DIRECT_CONNECT : IoEvent::AFTER_SET_DIRECT_CONNECT,
            $eventData
        );

        $this->directConnectRepository->commit();
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     * @throws Exception
     */
    public function readDirectConnect(Module $slave, Port $port, int $order): DirectConnectDto
    {
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_READ_DIRECT_CONNECT, [
            'slave' => $slave,
            'port' => $port,
            'order' => $order,
        ]);
        $directConnect = null;
        $hasMore = false;

        for ($i = 0;; ++$i) {
            $this->write($slave, self::COMMAND_READ_DIRECT_CONNECT, chr($port->getNumber()) . chr($order));
            $data = $this->read($slave, self::COMMAND_READ_DIRECT_CONNECT, self::COMMAND_READ_DIRECT_CONNECT_READ_LENGTH);
            $lastByte = $this->transformService->asciiToUnsignedInt($data, 3);

            if ($lastByte === self::DIRECT_CONNECT_READ_NOT_SET) {
                if ($i === self::DIRECT_CONNECT_READ_RETRY) {
                    throw new ReceiveError('Es ist kein Port gesetzt!', self::DIRECT_CONNECT_READ_NOT_SET);
                }

                continue;
            }

            if ($lastByte === self::DIRECT_CONNECT_READ_NOT_EXIST) {
                break;
            }

            $this->portRepository->startTransaction();

            try {
                $directConnect = $this->directConnectMapper->getDirectConnect($port, $data)
                    ->setInputPort($port)
                    ->setOrder($order)
                ;
                $this->modelManager->save($directConnect);
            } catch (AbstractException $exception) {
                $this->portRepository->rollback();

                throw $exception;
            }

            $this->portRepository->commit();
            $hasMore = (bool) (($this->transformService->asciiToUnsignedInt($data, 3) >> 5) & 1);

            break;
        }

        $eventData = $directConnect?->jsonSerialize() ?? [];
        $eventData['slave'] = $slave;
        $eventData['port'] = $port;
        $eventData['order'] = $order;
        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_READ_DIRECT_CONNECT, $eventData);

        return new DirectConnectDto($port, $hasMore, $directConnect);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws SaveError
     * @throws RepositoryDeleteError
     * @throws UpdateError
     * @throws WriteException
     * @throws JsonException
     */
    public function deleteDirectConnect(Module $module, DirectConnect $directConnect): void
    {
        $number = $directConnect->getInputPort()->getNumber();
        $order = $directConnect->getOrder();
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_DELETE_DIRECT_CONNECT, [
            'slave' => $module,
            'port' => $number,
            'order' => $order,
        ]);

        $this->directConnectRepository->startTransaction();

        try {
            $this->modelManager->delete($directConnect);
            $this->directConnectRepository->updateOrder($directConnect);
            $this->write(
                $module,
                self::COMMAND_DELETE_DIRECT_CONNECT,
                $this->getDeviceIdAsString($module->getDeviceId() ?? 0) . chr($number) . chr($order)
            );
        } catch (Exception $exception) {
            $this->directConnectRepository->rollback();

            throw $exception;
        }

        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_DELETE_DIRECT_CONNECT, [
            'slave' => $module,
            'port' => $number,
            'order' => $order,
        ]);

        $this->directConnectRepository->commit();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws RepositoryDeleteError
     * @throws SaveError
     * @throws WriteException
     */
    public function resetDirectConnect(Module $module, Port $port, bool $databaseOnly = false): void
    {
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_RESET_DIRECT_CONNECT, [
            'slave' => $module,
            'port' => $port,
            'databaseOnly' => $databaseOnly,
        ]);

        $this->directConnectRepository->startTransaction();

        try {
            $this->directConnectRepository->deleteByInputPort($port);

            if (!$databaseOnly) {
                $this->write(
                    $module,
                    self::COMMAND_RESET_DIRECT_CONNECT,
                    $this->getDeviceIdAsString($module->getDeviceId() ?? 0) . chr($port->getNumber())
                );
            }
        } catch (AbstractException $exception) {
            $this->directConnectRepository->rollback();

            throw $exception;
        }

        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_RESET_DIRECT_CONNECT, [
            'slave' => $module,
            'port' => $port,
            'databaseOnly' => $databaseOnly,
        ]);

        $this->directConnectRepository->commit();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws SaveError
     * @throws WriteException
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
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws SaveError
     * @throws WriteException
     */
    public function activateDirectConnect(Module $slave, bool $active = true): void
    {
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_ACTIVATE_DIRECT_CONNECT, ['slave' => $slave, 'active' => $active]);
        $this->write($slave, self::COMMAND_DIRECT_CONNECT_STATUS, chr($active ? 1 : 0));
        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_ACTIVATE_DIRECT_CONNECT, ['slave' => $slave, 'active' => $active]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws ReceiveError
     * @throws SaveError
     */
    public function isDirectConnectActive(Module $slave): bool
    {
        $this->eventService->fire($this->getEventClassName(), IoEvent::BEFORE_IS_DIRECT_CONNECT_ACTIVE, ['slave' => $slave]);

        $active = (bool) $this->transformService->asciiToUnsignedInt($this->read(
            $slave,
            self::COMMAND_DIRECT_CONNECT_STATUS,
            self::COMMAND_DIRECT_CONNECT_STATUS_READ_LENGTH
        ));

        $this->eventService->fire($this->getEventClassName(), IoEvent::AFTER_IS_DIRECT_CONNECT_ACTIVE, ['slave' => $slave, 'active' => $active]);

        return $active;
    }

    protected function getEventClassName(): string
    {
        return IoEvent::class;
    }
}
