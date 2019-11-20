<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Protocol;

use GibsonOS\Core\Exception\CreateError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Exception\Server\SendError;
use GibsonOS\Core\Exception\SetError;
use GibsonOS\Core\Service\UdpService as CoreUdpService;
use GibsonOS\Module\Hc\Service\MasterService;

class UdpService extends AbstractProtocol
{
    const SEND_PORT = 7363;

    const RECEIVE_PORT = 7339;

    const ENV_SERVER_IP = 'HC_SERVER_IP';

    /**
     * @var CoreUdpService
     */
    private $udpReceiveService;

    /**
     * @var string
     */
    private $serverIp;

    /**
     * @var string
     */
    private $subnet;

    /**
     * Udp constructor.
     *
     * @throws GetError
     */
    public function __construct()
    {
        $this->serverIp = getenv(self::ENV_SERVER_IP);

        if (empty($this->serverIp) || !is_string($this->serverIp)) {
            throw new GetError(
                sprintf(
                    'Server IP ist leer oder kein String. Umgebungsvariable %s muss gesetzt sein.',
                    self::ENV_SERVER_IP
                )
            );
        }

        $this->subnet = mb_substr($this->serverIp, 0, mb_strrpos($this->serverIp, '.'));
    }

    /**
     * @throws SetError
     * @throws CreateError
     */
    private function setReceiveServer()
    {
        $this->udpReceiveService = new CoreUdpService($this->serverIp, self::RECEIVE_PORT);
        $this->udpReceiveService->setTimeout(3);
    }

    /**
     * @throws SetError
     * @throws CreateError
     *
     * @return bool
     */
    public function receive()
    {
        if (!$this->udpReceiveService instanceof CoreUdpService) {
            $this->setReceiveServer();
        }

        try {
            $this->data = $this->udpReceiveService->receive(self::RECEIVE_LENGTH);
        } catch (ReceiveError $exception) {
            return false;
        }
        echo 'Receive Data: ' . $this->data . PHP_EOL;
        if (!$this->data) {
            return false;
        }

        return true;
    }

    /**
     * @param int    $type
     * @param string $data
     * @param int    $address
     *
     * @throws SendError
     * @throws SetError
     * @throws CreateError
     */
    public function send($type, $data, $address)
    {
        $udpSendService = new CoreUdpService($this->serverIp, self::SEND_PORT);
        $udpSendService->setTimeout(10);
        echo 'Send Data: ' . $data . PHP_EOL;
        $udpSendService->send(chr($type) . $data, $this->subnet . '.' . $address, self::SEND_PORT);
        $udpSendService->close();
    }

    /**
     * @throws ReceiveError
     * @throws SetError
     * @throws CreateError
     * @throws CreateError
     */
    public function receiveReadData()
    {
        $udpSendService = $this->createSendService();

        try {
            $this->data = $udpSendService->receive(self::RECEIVE_LENGTH);
        } catch (ReceiveError $exception) {
            $udpSendService->close();

            throw $exception;
        }

        $udpSendService->close();
    }

    /**
     * @throws SendError
     */
    public function sendReceiveReturn()
    {
        $this->udpReceiveService->send(
            chr(MasterService::TYPE_RECEIVE_RETURN),
            $this->subnet . '.' . $this->getMasterAddress(),
            self::RECEIVE_PORT
        );
    }

    /**
     * @param int $address
     *
     * @throws ReceiveError
     * @throws SetError
     * @throws CreateError
     * @throws CreateError
     */
    public function receiveReceiveReturn($address)
    {
        $udpSendService = $this->createSendService();

        try {
            $data = $udpSendService->receive(2);
        } catch (ReceiveError $exception) {
            $udpSendService->close();

            throw new ReceiveError('Empfangsbestätigung nicht erhalten!');
        }

        $udpSendService->close();

        if ($data != chr($address) . chr(MasterService::TYPE_RECEIVE_RETURN)) {
            throw new ReceiveError('Empfangsbestätigung nicht erhalten!');
        }
    }

    /**
     *@throws CreateError
     * @throws SetError
     *
     * @return CoreUdpService
     */
    private function createSendService()
    {
        $udpSendService = new CoreUdpService($this->serverIp, self::SEND_PORT);
        $udpSendService->setTimeout(3);

        return $udpSendService;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'udp';
    }
}
