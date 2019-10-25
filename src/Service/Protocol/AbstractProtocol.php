<?php
namespace GibsonOS\Module\Hc\Service\Protocol;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Exception\Server\SendError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Utility\Transform;

abstract class AbstractProtocol extends AbstractService
{
    const RECEIVE_LENGTH = 128;

    /**
     * @var string
     */
    protected $data;

    /**
     * @return bool
     */
    abstract public function receive();
    /**
     * @param int $type
     * @param string $data
     * @param int $address
     * @throws AbstractException
     */
    abstract public function send($type, $data, $address);
    /**
     * @return void
     * @throws SendError
     */
    abstract public function sendReceiveReturn();
    /**
     * @param int $address
     * @return void
     */
    abstract public function receiveReceiveReturn($address);
    /**
     * @return void
     */
    abstract public function receiveReadData();
    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @return int
     */
    public function getMasterAddress()
    {
        return Transform::asciiToInt($this->data, 0);
    }

    /**
     * @return int
     */
    public function getType()
    {
        return Transform::asciiToInt($this->data, 1);
    }

    /**
     * @return bool|string
     */
    public function getData()
    {
        return substr($this->data, 2, -1);
    }

    /**
     * @throws ReceiveError
     */
    public function checksumEqual()
    {
        //echo strlen($this->data) . PHP_EOL;
        //echo ord(substr($this->data, -1)) . ' !== ' . $this->getCheckSum() . PHP_EOL;
        if (ord(substr($this->data, -1)) !== $this->getCheckSum()) {
            throw new ReceiveError('Checksumme stimmt nicht überein!');
        }
    }

    /**
     * @return int
     */
    private function getCheckSum()
    {
        $checkSum = 0;

        for ($i = 0; $i < strlen($this->data) - 1; $i++) {
            $checkSum += ord($this->data[$i]);
        }

        return $checkSum % 256;
    }
}