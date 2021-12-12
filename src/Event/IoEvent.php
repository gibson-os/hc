<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Attribute\Event;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\ServiceManagerService;
use GibsonOS\Module\Hc\Dto\Parameter\Io\PortParameter;
use GibsonOS\Module\Hc\Dto\Parameter\SlaveParameter;
use GibsonOS\Module\Hc\Event\Describer\IoDescriber;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use Psr\Log\LoggerInterface;

#[Event('I/O')]
class IoEvent extends AbstractHcEvent
{
    #[Event\Trigger('Vor auslesen eines Ports', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
    ])]
    public const BEFORE_READ_PORT = 'beforeReadPort';

    #[Event\Trigger('Nach auslesen eines Ports', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
    ])]
    public const AFTER_READ_PORT = 'afterReadPort';

    public const BEFORE_WRITE_PORT = 'beforeWritePort';

    public const AFTER_WRITE_PORT = 'afterWritePort';

    public const BEFORE_READ_PORTS_FROM_EEPROM = 'beforeReadPortsFromEeprom';

    public const AFTER_READ_PORTS_FROM_EEPROM = 'afterReadPortsFromEeprom';

    public const BEFORE_WRITE_PORTS_TO_EEPROM = 'beforeWritePortsToEeprom';

    public const AFTER_WRITE_PORTS_TO_EEPROM = 'afterWritePortsToEeprom';

    public const BEFORE_READ_PORTS = 'beforeReadPorts';

    public const AFTER_READ_PORTS = 'afterReadPorts';

    public const BEFORE_ADD_DIRECT_CONNECT = 'beforeAddDirectConnect';

    public const AFTER_ADD_DIRECT_CONNECT = 'afterAddDirectConnect';

    public const BEFORE_SET_DIRECT_CONNECT = 'beforeSetDirectConnect';

    public const AFTER_SET_DIRECT_CONNECT = 'afterSetDirectConnect';

    public const BEFORE_SAVE_DIRECT_CONNECT = 'beforeSaveDirectConnect';

    public const AFTER_SAVE_DIRECT_CONNECT = 'afterSaveDirectConnect';

    public const BEFORE_READ_DIRECT_CONNECT = 'beforeReadDirectConnect';

    public const AFTER_READ_DIRECT_CONNECT = 'afterReadDirectConnect';

    public const BEFORE_DELETE_DIRECT_CONNECT = 'beforeDeleteDirectConnect';

    public const AFTER_DELETE_DIRECT_CONNECT = 'afterDeleteDirectConnect';

    public const BEFORE_RESET_DIRECT_CONNECT = 'beforeResetDirectConnect';

    public const AFTER_RESET_DIRECT_CONNECT = 'afterResetDirectConnect';

    public const BEFORE_DEFRAGMENT_DIRECT_CONNECT = 'beforeDefragmentDirectConnect';

    public const AFTER_DEFRAGMENT_DIRECT_CONNECT = 'afterDefragmentDirectConnect';

    public const BEFORE_ACTIVATE_DIRECT_CONNECT = 'beforeActivateDirectConnect';

    public const AFTER_ACTIVATE_DIRECT_CONNECT = 'afterActivateDirectConnect';

    public const BEFORE_IS_DIRECT_CONNECT_ACTIVE = 'beforeIsDirectConnectActive';

    public const AFTER_IS_DIRECT_CONNECT_ACTIVE = 'afterIsDirectConnectActive';

    public function __construct(
        IoDescriber $describer,
        ServiceManagerService $serviceManagerService,
        TypeRepository $typeRepository,
        LoggerInterface $logger,
        private IoService $ioService
    ) {
        parent::__construct($describer, $serviceManagerService, $typeRepository, $logger, $this->ioService);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readPort(Module $slave, array $params): array
    {
        return $this->ioService->readPort($slave, $params['number']);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readPortsFromEeprom(Module $slave): void
    {
        $this->ioService->readPortsFromEeprom($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function getPorts(Module $slave): array
    {
        return $this->ioService->getPorts($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readDirectConnect(Module $slave, array $params): array
    {
        return $this->ioService->readDirectConnect($slave, $params['port'], $params['order']);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function isDirectConnectActive(Module $slave): bool
    {
        return $this->ioService->isDirectConnectActive($slave);
    }

    /**
     * @throws AbstractException
     */
    public function setPort(Module $slave, array $params): void
    {
        $this->ioService->setPort(
            $slave,
            $params['number'],
            $params[IoService::ATTRIBUTE_PORT_KEY_NAME],
            $params[IoService::ATTRIBUTE_PORT_KEY_DIRECTION],
            $params[IoService::ATTRIBUTE_PORT_KEY_PULL_UP],
            $params[IoService::ATTRIBUTE_PORT_KEY_DELAY],
            $params[IoService::ATTRIBUTE_PORT_KEY_PWM],
            $params[IoService::ATTRIBUTE_PORT_KEY_BLINK],
            $params[IoService::ATTRIBUTE_PORT_KEY_FADE_IN],
            $params[IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES]
        );
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writePortsToEeprom(Module $slave): void
    {
        $this->ioService->writePortsToEeprom($slave);
    }

    /**
     * @throws AbstractException
     */
    public function saveDirectConnect(Module $slave, array $params): void
    {
        $this->ioService->saveDirectConnect(
            $slave,
            $params['inputPort'],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE],
            $params['order'],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB]
        );
    }

    /**
     * @throws AbstractException
     */
    public function deleteDirectConnect(Module $slave, array $params): void
    {
        $this->ioService->deleteDirectConnect($slave, $params['port'], $params['order']);
    }

    /**
     * @throws AbstractException
     */
    public function resetDirectConnect(Module $slave, array $params): void
    {
        $this->ioService->resetDirectConnect($slave, $params['port'], $params['databaseOnly']);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function defragmentDirectConnect(Module $slave): void
    {
        $this->ioService->defragmentDirectConnect($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function activateDirectConnect(Module $slave, array $params): void
    {
        $this->ioService->activateDirectConnect($slave, $params['active']);
    }
}
