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
use GibsonOS\Module\Hc\Exception\TransformException;
use GibsonOS\Module\Hc\Mapper\BusMessageMapper;
use GibsonOS\Module\Hc\Service\MasterService;
use Psr\Log\LoggerInterface;

class UdpService extends AbstractService implements ProtocolInterface
{
    const RECEIVE_PORT = 42000;

    const START_PORT = 43000;

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
    private function setReceiveServer(): void
    {
        if ($this->udpReceiveService instanceof CoreUdpService) {
            return;
        }

        $this->logger->debug(sprintf('Start UDP receive server %s:%d', $this->ip, self::RECEIVE_PORT));
        $this->udpReceiveService = new CoreUdpService($this->logger, $this->ip, self::RECEIVE_PORT);
        $this->udpReceiveService->setTimeout(3);
    }

    /**
     * @throws SetError
     * @throws CreateError
     */
    public function receive(): ?BusMessage
    {
        $this->setReceiveServer();

        try {
            $this->logger->debug(sprintf('Receive message'));

            return $this->busMessageMapper->mapFromUdpMessage(
                $this->udpReceiveService->receive(self::RECEIVE_LENGTH)
            );
        } catch (ReceiveError $exception) {
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
        $udpSendService = $this->createSendService($busMessage->getPort() ?? self::RECEIVE_PORT);
        $udpSendService->setTimeout(10);
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
            $this->logger->debug(sprintf('Receive read data UDP message'));

            $data = $udpSendService->receive(self::RECEIVE_LENGTH);
        } finally {
            $udpSendService->close();
        }

        return $this->busMessageMapper->mapFromUdpMessage($data);
    }

    /**
     * @throws SendError
     */
    public function sendReceiveReturn(BusMessage $busMessage): void
    {
        $this->logger->debug(sprintf('Send receive return to %s', $busMessage->getMasterAddress()));
        $this->udpReceiveService->send(new UdpMessage(
            $busMessage->getMasterAddress(),
            $busMessage->getPort() ?? self::START_PORT,
            chr(MasterService::TYPE_RECEIVE_RETURN)
        ));
    }

    /**
     * @throws ReceiveError
     * @throws SetError
     * @throws CreateError
     * @throws CreateError
     */
    public function receiveReceiveReturn(BusMessage $busMessage): void
    {
        $udpSendService = $this->createSendService($busMessage->getPort() ?? self::RECEIVE_PORT);

        try {
            $this->logger->debug(sprintf('Receive receive return'));
            $data = $udpSendService->receive(2);
        } catch (ReceiveError $exception) {
            throw new ReceiveError('Receive return not received!');
        } finally {
            $udpSendService->close();
        }

        if (
            $data->getIp() !== $busMessage->getMasterAddress() ||
            $data->getMessage() !== chr(MasterService::TYPE_RECEIVE_RETURN)
        ) {
            throw new ReceiveError('Receive return data not equal!');
        }
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
