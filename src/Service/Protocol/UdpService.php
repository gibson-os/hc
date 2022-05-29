<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Protocol;

use GibsonOS\Core\Exception\CreateError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Exception\Server\SendError;
use GibsonOS\Core\Exception\SetError;
use GibsonOS\Core\Service\UdpService as CoreUdpService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Exception\TransformException;
use GibsonOS\Module\Hc\Mapper\BusMessageMapper;
use GibsonOS\Module\Hc\Service\MasterService;
use Psr\Log\LoggerInterface;

class UdpService implements ProtocolInterface
{
    public const RECEIVE_PORT = 42000;

    public const START_PORT = 43000;

    private ?CoreUdpService $udpReceiveService = null;

    private string $ip = '0';

    public function __construct(
        private readonly BusMessageMapper $busMessageMapper,
        private readonly LoggerInterface $logger
    ) {
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
    private function getReceiveServer(): CoreUdpService
    {
        if ($this->udpReceiveService instanceof CoreUdpService) {
            return $this->udpReceiveService;
        }

        $this->logger->debug(sprintf('Start UDP receive server %s:%d', $this->ip, self::RECEIVE_PORT));
        $this->udpReceiveService = new CoreUdpService($this->logger, $this->ip, self::RECEIVE_PORT);
        $this->udpReceiveService->setTimeout(3);

        return $this->udpReceiveService;
    }

    /**
     * @throws CreateError
     * @throws SetError
     * @throws TransformException
     */
    public function receive(): ?BusMessage
    {
        try {
            $this->logger->debug('Receive message');

            return $this->busMessageMapper->mapFromUdpMessage(
                $this->getReceiveServer()->receive(self::RECEIVE_LENGTH)
            );
        } catch (ReceiveError) {
            $this->logger->debug('Nothing received');

            return null;
        }
    }

    /**
     * @throws CreateError
     * @throws SendError
     * @throws SetError
     */
    public function send(BusMessage $busMessage): void
    {
        $udpSendService = $this->createSendService($busMessage->getPort() ?? self::START_PORT);
        $udpSendService->setTimeout();
        $udpSendService->send($this->busMessageMapper->mapToUdpMessage($busMessage));
        $udpSendService->close();
    }

    /**
     * @throws CreateError
     * @throws ReceiveError
     * @throws SetError
     * @throws TransformException
     */
    public function receiveReadData(?int $port): BusMessage
    {
        $udpSendService = $this->createSendService($port ?? self::RECEIVE_PORT);

        try {
            $this->logger->debug('Receive read data UDP message');

            $data = $udpSendService->receive(self::RECEIVE_LENGTH);
        } finally {
            $udpSendService->close();
        }

        return $this->busMessageMapper->mapFromUdpMessage($data);
    }

    /**
     * @throws CreateError
     * @throws ReceiveError
     * @throws SetError
     * @throws TransformException
     */
    public function receiveReceiveReturn(BusMessage $busMessage): void
    {
        $udpSendService = $this->createSendService($busMessage->getPort() ?? self::RECEIVE_PORT);

        try {
            $this->logger->debug('Receive receive return');
            $receivedBusMessage = $this->busMessageMapper->mapFromUdpMessage($udpSendService->receive(6));
        } catch (ReceiveError) {
            throw new ReceiveError('Receive return not received!');
        } finally {
            $udpSendService->close();
        }

        if ($receivedBusMessage->getMasterAddress() !== $busMessage->getMasterAddress()) {
            throw new ReceiveError(sprintf(
                'IP address %s not equal with received IP address %s',
                $busMessage->getMasterAddress(),
                $receivedBusMessage->getMasterAddress()
            ));
        }

        if ($receivedBusMessage->getType() !== MasterService::TYPE_RECEIVE_RETURN) {
            throw new ReceiveError('Received return data not equal!');
        }

        $this->logger->debug('Received receive return');
    }

    /**
     * @throws CreateError
     * @throws SetError
     */
    private function createSendService(int $port): CoreUdpService
    {
        $udpSendService = new CoreUdpService($this->logger, $this->ip, $port);
        $udpSendService->setTimeout(3);

        return $udpSendService;
    }

    public function getName(): string
    {
        return 'udp';
    }
}
