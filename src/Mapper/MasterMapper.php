<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper;

use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Service\TransformService;
use Psr\Log\LoggerInterface;

class MasterMapper
{
    public function __construct(
        private readonly TransformService $transformService,
        private readonly LoggerInterface $logger,
    ) {
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
                $checkSum ?? 0,
            ));
        }
    }

    /**
     * @throws GetError
     */
    public function extractSlaveDataFromMessage(BusMessage $busMessage): void
    {
        $data = $busMessage->getData();

        if ($data === null || $data === '') {
            throw new GetError('No slave data transmitted!');
        }

        $this->logger->debug('Get Slave data from message');

        $busMessage->setSlaveAddress($this->transformService->asciiToUnsignedInt($data, 0));
        $busMessage->setCommand($this->transformService->asciiToUnsignedInt($data, 1));
        $busMessage->setData(substr($data, 2) ?: null);
    }

    public function getCheckSum(BusMessage $busMessage): ?int
    {
        $checkSum = $busMessage->getType();

        foreach (explode('.', $busMessage->getMasterAddress()) as $ipByte) {
            $checkSum += (int) $ipByte;
        }

        $data = $busMessage->getData();

        if ($data !== null && $data !== '') {
            for ($i = 0; $i < strlen($data); ++$i) {
                $checkSum += ord($data[$i]);
            }
        }

        return $checkSum % 256;
    }
}
