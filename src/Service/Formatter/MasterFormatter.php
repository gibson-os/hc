<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Formatter;

use GibsonOS\Core\Exception\Server\ReceiveError;
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

    public function getMasterAddress(string $data): int
    {
        return $this->transform->asciiToUnsignedInt($data, 0);
    }

    public function getType(string $data): int
    {
        return $this->transform->asciiToUnsignedInt($data, 1);
    }

    public function getData(string $data): string
    {
        return (string) substr($data, 2, -1);
    }

    /**
     * @throws ReceiveError
     */
    public function checksumEqual(string $data): void
    {
        //echo strlen($this->data) . PHP_EOL;
        //echo ord(substr($this->data, -1)) . ' !== ' . $this->getCheckSum() . PHP_EOL;
        if (ord(substr($data, -1)) !== $this->getCheckSum($data)) {
            throw new ReceiveError('Checksumme stimmt nicht überein!');
        }
    }

    private function getCheckSum(string $data): int
    {
        $checkSum = 0;

        for ($i = 0; $i < strlen($data) - 1; ++$i) {
            $checkSum += ord($data[$i]);
        }

        return $checkSum % 256;
    }
}
