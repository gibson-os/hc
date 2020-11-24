<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Protocol;

use GibsonOS\Core\Dto\UdpMessage;
use GibsonOS\Core\Exception\CreateError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Exception\Server\SendError;
use GibsonOS\Core\Exception\SetError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Core\Service\UdpService as CoreUdpService;
use GibsonOS\Module\Hc\Service\MasterService;

class NewUdpService extends AbstractService implements ProtocolInterface
{
    const SEND_PORT = 7363;

    const RECEIVE_PORT = 7339;

    /**
     * @var CoreUdpService
     */
    private $udpReceiveService;

    /**
     * @var string|null
     */
    private $ip;

    public function setIp(string $ip): NewUdpService
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @throws SetError
     * @throws CreateError
     */
    private function setReceiveServer()
    {
        if ($this->ip === null) {
            throw new CreateError('Server IP is null');
        }

        $this->udpReceiveService = new CoreUdpService($this->ip, self::RECEIVE_PORT);
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
    public function send(int $type, string $data, string $address): void
    {
        $udpSendService = $this->createSendService();
        $udpSendService->setTimeout(10);
        $udpSendService->send(new UdpMessage($address, self::SEND_PORT, chr($type) . $data));
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
    public function sendReceiveReturn(string $ip): void
    {
        $this->udpReceiveService->send(new UdpMessage(
            $ip,
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
    public function receiveReceiveReturn(string $ip): void
    {
        $udpSendService = $this->createSendService();

        try {
            $data = $udpSendService->receive(2);
        } catch (ReceiveError $exception) {
            throw new ReceiveError('Empfangsbestätigung nicht erhalten!');
        } finally {
            $udpSendService->close();
        }

        if ($data->getIp() !== $ip ||
            $data->getMessage() !== chr(MasterService::TYPE_RECEIVE_RETURN)
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
        if ($this->ip === null) {
            throw new CreateError('Server IP is null');
        }

        $udpSendService = new CoreUdpService($this->ip, self::SEND_PORT);
        $udpSendService->setTimeout(3);

        return $udpSendService;
    }

    public function getName(): string
    {
        return 'udp';
    }
}
