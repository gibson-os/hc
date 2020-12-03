<?php declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper;

use GibsonOS\Core\Dto\UdpMessage;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Service\TransformService;

class BusMessageMapper
{
    /**
     * @var TransformService
     */
    private $transformService;

    public function __construct(TransformService $transformService)
    {
        $this->transformService = $transformService;
    }

    /**
     * @throws GetError
     */
    public function mapToUdpMessage(BusMessage $busMessage, int $port): UdpMessage
    {
        $message = '';
        $slaveAddress = $busMessage->getSlaveAddress();
        $command = $busMessage->getCommand();
        $data = $busMessage->getData();

        if ($slaveAddress !== null) {
            $message .= ($slaveAddress << 1) | (int) $busMessage->isWrite();
        }

        if ($command !== null) {
            $message .= chr($command);
        }

        $message .= chr($busMessage->getType());

        if ($data !== null) {
            $message .= $data;
        }

        return new UdpMessage($busMessage->getMasterAddress(), $port, $message);
    }

    public function mapFromUdpMessage(UdpMessage $udpMessage): BusMessage
    {
        return (new BusMessage(
            $this->transformIpFromUdpMessage($udpMessage),
            $this->transformService->asciiToUnsignedInt($udpMessage->getMessage(), 4)
        ))
            ->setData(substr($udpMessage->getMessage(), 5, -1) ?: null)
            ->setChecksum(ord(substr($udpMessage->getMessage(), -1)))
        ;
    }

    private function transformIpFromUdpMessage(UdpMessage $udpMessage): string
    {
        $ipParts = [];

        for ($i = 0; $i < 4; ++$i) {
            $ipParts[] = $this->transformService->asciiToUnsignedInt($udpMessage->getMessage(), $i);
        }

        return implode('.', $ipParts);
    }
}
