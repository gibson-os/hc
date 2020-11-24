<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Protocol;

use GibsonOS\Core\Dto\UdpMessage;
use GibsonOS\Core\Exception\CreateError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Exception\Server\SendError;
use GibsonOS\Core\Exception\SetError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Core\Service\UdpService as CoreUdpService;
use GibsonOS\Module\Hc\Service\MasterService;

class UdpService extends AbstractService implements ProtocolInterface
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
        $this->serverIp = (string) getenv(self::ENV_SERVER_IP);

        if (empty($this->serverIp)) {
            throw new GetError(
                sprintf(
                    'Server IP ist leer oder kein String. Umgebungsvariable %s muss gesetzt sein.',
                    self::ENV_SERVER_IP
                )
            );
        }

        $this->subnet = mb_substr($this->serverIp, 0, mb_strrpos($this->serverIp, '.') ?: null);
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
     */
    public function receive(): ?string
    {
        if (!$this->udpReceiveService instanceof CoreUdpService) {
            $this->setReceiveServer();
        }

        try {
            return $this->udpReceiveService->receive(self::RECEIVE_LENGTH)->getMessage();
        } catch (ReceiveError $exception) {
            return null;
        }
    }

    /**
     * @throws SendError
     * @throws SetError
     * @throws CreateError
     */
    public function send(int $type, string $data, int $address): void
    {
        $udpSendService = new CoreUdpService($this->serverIp, self::SEND_PORT);
        $udpSendService->setTimeout(10);
        $udpSendService->send(new UdpMessage($this->subnet . '.' . $address, self::SEND_PORT, chr($type) . $data));
        $udpSendService->close();
    }

    /**
     * @throws ReceiveError
     * @throws SetError
     * @throws CreateError
     * @throws CreateError
     */
    public function receiveReadData(): string
    {
        $udpSendService = $this->createSendService();

        try {
            $data = $udpSendService->receive(self::RECEIVE_LENGTH)->getMessage();
        } catch (ReceiveError $exception) {
            $udpSendService->close();

            throw $exception;
        }

        $udpSendService->close();

        return $data;
    }

    /**
     * @throws SendError
     */
    public function sendReceiveReturn(int $address): void
    {
        $this->udpReceiveService->send(new UdpMessage(
            $this->subnet . '.' . $address,
            self::RECEIVE_PORT,
            chr(MasterService::TYPE_RECEIVE_RETURN)
        ));
    }

    /**
     * @throws ReceiveError
     * @throws SetError
     * @throws CreateError
     * @throws CreateError
     */
    public function receiveReceiveReturn(int $address): void
    {
        $udpSendService = $this->createSendService();

        try {
            $data = $udpSendService->receive(2);
        } catch (ReceiveError $exception) {
            throw new ReceiveError('Empfangsbestätigung nicht erhalten!');
        } finally {
            $udpSendService->close();
        }

        if (
            $data->getIp() !== $this->subnet . '.' . $address ||
            $data->getMessage() !== chr($address) . chr(MasterService::TYPE_RECEIVE_RETURN)
        ) {
            throw new ReceiveError('Empfangsbestätigung nicht erhalten!');
        }
    }

    /**
     * @throws CreateError
     * @throws SetError
     */
    private function createSendService(): CoreUdpService
    {
        $udpSendService = new CoreUdpService($this->serverIp, self::SEND_PORT);
        $udpSendService->setTimeout(3);

        return $udpSendService;
    }

    public function getName(): string
    {
        return 'udp';
    }
}
