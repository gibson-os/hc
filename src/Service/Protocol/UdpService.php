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
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Mapper\BusMessageMapper;
use GibsonOS\Module\Hc\Service\MasterService;
use Psr\Log\LoggerInterface;

class UdpService extends AbstractService implements ProtocolInterface
{
    const SEND_PORT = 7363;

    const RECEIVE_PORT = 7339;

    /**
     * @var CoreUdpService
     */
    private $udpReceiveService;

    /**
     * @var string
     */
    private $ip = '0';

    /**
     * @var BusMessageMapper
     */
    private $busMessageMapper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(BusMessageMapper $busMessageMapper, LoggerInterface $logger)
    {
        $this->busMessageMapper = $busMessageMapper;
        $this->logger = $logger;
    }

    public function setIp(string $ip): UdpService
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
        $this->logger->debug(sprintf('Start UDP receive server %s:%d', $this->ip, self::RECEIVE_PORT));
        $this->udpReceiveService = new CoreUdpService($this->ip, self::RECEIVE_PORT);
        $this->udpReceiveService->setTimeout(3);
    }

    /**
     * @throws SetError
     * @throws CreateError
     */
    public function receive(): ?BusMessage
    {
        if (!$this->udpReceiveService instanceof CoreUdpService) {
            $this->setReceiveServer();
        }

        try {
            $this->logger->debug('Receive UDP data');

            return $this->busMessageMapper->mapFromUdpMessage(
                $this->udpReceiveService->receive(self::RECEIVE_LENGTH)
            );
        } catch (ReceiveError $exception) {
            return null;
        }
    }

    /**
     * @throws SendError
     * @throws SetError
     * @throws CreateError
     */
    public function send(BusMessage $busMessage): void
    {
        $udpSendService = $this->createSendService();
        $udpSendService->setTimeout(10);
        $udpSendService->send($this->busMessageMapper->mapToUdpMessage($busMessage, self::SEND_PORT));
        $udpSendService->close();
    }

    /**
     * @throws ReceiveError
     * @throws SetError
     * @throws CreateError
     * @throws CreateError
     */
    public function receiveReadData(): BusMessage
    {
        $udpSendService = $this->createSendService();

        try {
            $data = $udpSendService->receive(self::RECEIVE_LENGTH);
        } finally {
            $udpSendService->close();
        }

        return $this->busMessageMapper->mapFromUdpMessage($data);
    }

    /**
     * @throws SendError
     */
    public function sendReceiveReturn(string $address): void
    {
        $this->udpReceiveService->send(new UdpMessage(
            $address,
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
    public function receiveReceiveReturn(string $address): void
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
            $data->getIp() !== $address ||
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
        $udpSendService = new CoreUdpService($this->ip, self::SEND_PORT);
        $udpSendService->setTimeout(3);

        return $udpSendService;
    }

    public function getName(): string
    {
        return 'udp';
    }
}
