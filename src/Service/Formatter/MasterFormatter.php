<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Formatter;

use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\TransformService;
use Psr\Log\LoggerInterface;

class MasterFormatter implements FormatterInterface
{
    private TransformService $transformService;

    private LoggerInterface $logger;

    public function __construct(TransformService $transform, LoggerInterface $logger)
    {
        $this->transformService = $transform;
        $this->logger = $logger;
    }

    public function render(Log $log): ?string
    {
        return null;
    }

    public function text(Log $log): ?string
    {
        return null;
    }

    public function command(Log $log): ?string
    {
        return null;
    }

    /**
     * @throws ReceiveError
     */
    public function checksumEqual(BusMessage $busMessage): void
    {
        $checkSum = $this->getCheckSum($busMessage);

        if ($busMessage->getChecksum() !== $checkSum) {
            throw new ReceiveError(sprintf(
                'Checksum not equal (%d === %d)!',
                $busMessage->getChecksum() ?? 0,
                $checkSum ?? 0
            ));
        }
    }

    /**
     * @throws GetError
     */
    public function extractSlaveDataFromMessage(BusMessage $busMessage): void
    {
        $data = $busMessage->getData();

        if (empty($data)) {
            throw new GetError('No slave data transmitted!');
        }

        $this->logger->debug('Get Slave data from message');

        $busMessage->setSlaveAddress($this->transformService->asciiToUnsignedInt($data, 0));
        $busMessage->setCommand($this->transformService->asciiToUnsignedInt($data, 1));
        $busMessage->setData(substr($data, 2) ?: null);
    }

    private function getCheckSum(BusMessage $busMessage): ?int
    {
        $checkSum = $busMessage->getType();

        foreach (explode('.', $busMessage->getMasterAddress()) as $ipByte) {
            $checkSum += (int) $ipByte;
        }

        $data = $busMessage->getData();

        if (!empty($data)) {
            for ($i = 0; $i < strlen($data); ++$i) {
                $checkSum += ord($data[$i]);
            }
        }

        return $checkSum % 256;
    }
}
