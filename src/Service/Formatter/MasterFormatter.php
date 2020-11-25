<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Formatter;

use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\TransformService;

class MasterFormatter implements FormatterInterface
{
    /**
     * @var TransformService
     */
    private $transform;

    public function __construct(TransformService $transform)
    {
        $this->transform = $transform;
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
        if ($busMessage->getChecksum() !== $this->getCheckSum($busMessage->getData())) {
            throw new ReceiveError('Checksumme stimmt nicht überein!');
        }
    }

    private function getCheckSum(?string $data): ?int
    {
        if (empty($data)) {
            return null;
        }

        $checkSum = 0;

        for ($i = 0; $i < strlen($data) - 1; ++$i) {
            $checkSum += ord($data[$i]);
        }

        return $checkSum % 256;
    }
}
