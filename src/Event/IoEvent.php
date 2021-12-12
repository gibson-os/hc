<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\ServiceManagerService;
use GibsonOS\Module\Hc\Event\Describer\IoDescriber;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use Psr\Log\LoggerInterface;

class IoEvent extends AbstractHcEvent
{
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
