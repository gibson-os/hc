<?php
namespace GibsonOS\Module\Hc\Service\Event;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Service\Slave\Io as IoService;

class Io extends AbstractHc
{
    /**
     * @var IoService $slave
     */
    protected $slave;

    /**
     * @param array $params
     * @return array
     * @throws AbstractException
     * @throws ReceiveError
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
     * @return array
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function getPorts()
    {
        return $this->slave->getPorts();
    }

    /**
     * @param array $params
     * @return array
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readDirectConnect($params)
    {
        return $this->slave->readDirectConnect($params['port'], $params['order']);
    }

    /**
     * @return bool
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function isDirectConnectActive()
    {
        return $this->slave->isDirectConnectActive();
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function setPort($params)
    {
        $this->slave->setPort(
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
     */
    public function writePortsToEeprom()
    {
        $this->slave->writePortsToEeprom();
    }

    /**
     * @param array $params
     * @throws AbstractException
     */
    public function saveDirectConnect($params)
    {
        $this->slave->saveDirectConnect(
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
     * @param array $params
     * @throws AbstractException
     */
    public function deleteDirectConnect($params)
    {
        $this->slave->deleteDirectConnect($params['port'], $params['order']);
    }

    /**
     * @param array $params
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
     * @throws AbstractException
     */
    public function activateDirectConnect($params)
    {
        $this->slave->activateDirectConnect($params['active']);
    }
}