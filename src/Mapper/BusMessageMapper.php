<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper;

use GibsonOS\Core\Dto\UdpMessage;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Exception\TransformException;
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

    public function mapToUdpMessage(BusMessage $busMessage): UdpMessage
    {
        $message = chr($busMessage->getType());
        $slaveAddress = $busMessage->getSlaveAddress();
        $command = $busMessage->getCommand();
        $data = $busMessage->getData();

        if ($slaveAddress !== null) {
            $message .= chr(($slaveAddress << 1) | (int) $busMessage->isWrite());
        }

        if ($command !== null) {
            $message .= chr($command);
        }

        if ($data !== null) {
            $message .= $data;
        }

        return new UdpMessage($busMessage->getMasterAddress(), $busMessage->getPort() ?? 0, $message);
    }

    /**
     * @throws TransformException
     */
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

    /**
     * @throws TransformException
     */
    private function transformIpFromUdpMessage(UdpMessage $udpMessage): string
    {
        $ipParts = [];
        $message = $udpMessage->getMessage();

        if (strlen($message) < 4) {
            throw new TransformException('Message has no IP!');
        }

        for ($i = 0; $i < 4; ++$i) {
            $ipParts[] = $this->transformService->asciiToUnsignedInt($message, $i);
        }

        return implode('.', $ipParts);
    }
}
