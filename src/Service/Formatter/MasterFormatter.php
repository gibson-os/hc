<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Formatter;

use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\TransformService;

class MasterFormatter implements FormatterInterface
{
    /**
     * @var TransformService
     */
    private $transformService;

    public function __construct(TransformService $transform)
    {
        $this->transformService = $transform;
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
     * @throws GetError
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
