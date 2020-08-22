<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\Slave\IoService as IoSlave;

class IoEvent extends AbstractHcEvent
{
    /**
     * @var IoSlave
     */
    protected $slave;

    /**
     * @param array $params
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return array
     */
    public function readPort(Module $slave, $params)
    {
        return $this->slave->readPort($slave, $params['number']);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readPortsFromEeprom(Module $slave): void
    {
        $this->slave->readPortsFromEeprom($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function getPorts(Module $slave): array
    {
        return $this->slave->getPorts($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readDirectConnect(Module $slave, array $params): array
    {
        return $this->slave->readDirectConnect($slave, $params['port'], $params['order']);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function isDirectConnectActive(Module $slave): bool
    {
        return $this->slave->isDirectConnectActive($slave);
    }

    /**
     * @throws AbstractException
     */
    public function setPort(Module $slave, array $params): void
    {
        $this->slave->setPort(
            $slave,
            $params['number'],
            $params[IoSlave::ATTRIBUTE_PORT_KEY_NAME],
            $params[IoSlave::ATTRIBUTE_PORT_KEY_DIRECTION],
            $params[IoSlave::ATTRIBUTE_PORT_KEY_PULL_UP],
            $params[IoSlave::ATTRIBUTE_PORT_KEY_DELAY],
            $params[IoSlave::ATTRIBUTE_PORT_KEY_PWM],
            $params[IoSlave::ATTRIBUTE_PORT_KEY_BLINK],
            $params[IoSlave::ATTRIBUTE_PORT_KEY_FADE_IN],
            $params[IoSlave::ATTRIBUTE_PORT_KEY_VALUE_NAMES]
        );
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writePortsToEeprom(Module $slave): void
    {
        $this->slave->writePortsToEeprom($slave);
    }

    /**
     * @throws AbstractException
     */
    public function saveDirectConnect(Module $slave, array $params): void
    {
        $this->slave->saveDirectConnect(
            $slave,
            $params['inputPort'],
            $params[IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE],
            $params['order'],
            $params[IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT],
            $params[IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE],
            $params[IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM],
            $params[IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK],
            $params[IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN],
            $params[IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB]
        );
    }

    /**
     * @throws AbstractException
     */
    public function deleteDirectConnect(Module $slave, array $params): void
    {
        $this->slave->deleteDirectConnect($slave, $params['port'], $params['order']);
    }

    /**
     * @throws AbstractException
     */
    public function resetDirectConnect(Module $slave, array $params): void
    {
        $this->slave->resetDirectConnect($slave, $params['port'], $params['databaseOnly']);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function defragmentDirectConnect(Module $slave): void
    {
        $this->slave->defragmentDirectConnect($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function activateDirectConnect(Module $slave, array $params): void
    {
        $this->slave->activateDirectConnect($slave, $params['active']);
    }
}
