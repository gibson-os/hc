<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Event;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Service\Slave\IoService as IoSlave;

class IoService extends AbstractHcService
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
     *
     * @return array
     */
    public function readPort($params)
    {
        return $this->slave->readPort($params['number']);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readPortsFromEeprom()
    {
        $this->slave->readPortsFromEeprom();
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return array
     */
    public function getPorts()
    {
        return $this->slave->getPorts();
    }

    /**
     * @param array $params
     *
     * @throws AbstractException
     * @throws ReceiveError
     *
     * @return array
     */
    public function readDirectConnect($params)
    {
        return $this->slave->readDirectConnect($params['port'], $params['order']);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     *
     * @return bool
     */
    public function isDirectConnectActive()
    {
        return $this->slave->isDirectConnectActive();
    }

    /**
     * @param array $params
     *
     * @throws AbstractException
     */
    public function setPort($params)
    {
        $this->slave->setPort(
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
     */
    public function writePortsToEeprom()
    {
        $this->slave->writePortsToEeprom();
    }

    /**
     * @param array $params
     *
     * @throws AbstractException
     */
    public function saveDirectConnect($params)
    {
        $this->slave->saveDirectConnect(
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
     * @param array $params
     *
     * @throws AbstractException
     */
    public function deleteDirectConnect($params)
    {
        $this->slave->deleteDirectConnect($params['port'], $params['order']);
    }

    /**
     * @param array $params
     *
     * @throws AbstractException
     */
    public function resetDirectConnect($params)
    {
        $this->slave->resetDirectConnect($params['port'], $params['databaseOnly']);
    }

    /**
     * @throws AbstractException
     */
    public function defragmentDirectConnect()
    {
        $this->slave->defragmentDirectConnect();
    }

    /**
     * @param array $params
     *
     * @throws AbstractException
     */
    public function activateDirectConnect($params)
    {
        $this->slave->activateDirectConnect($params['active']);
    }
}
