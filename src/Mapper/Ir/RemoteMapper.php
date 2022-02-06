<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper\Ir;

use GibsonOS\Module\Hc\Dto\Ir\Remote;
use GibsonOS\Module\Hc\Formatter\IrFormatter;

class RemoteMapper
{
    public function __construct(private IrFormatter $irFormatter)
    {
    }

    public function mapRemote(string $name, string $background = null, int $id = null, array $keys = []): Remote
    {
        return new Remote(
            $name,
            $id,
            array_map(
                fn (array $key): Remote\Key => $this->mapRemoteKey($key),
                $keys
            ),
            $background
        );
    }

    public function mapRemoteKey(array $data): Remote\Key
    {
        return new Remote\Key(
            $data['name'],
            $data['width'],
            $data['height'],
            $data['top'],
            $data['left'],
            $data['borderTop'],
            $data['borderRight'],
            $data['borderBottom'],
            $data['borderLeft'],
            $data['borderRadiusTopLeft'],
            $data['borderRadiusTopRight'],
            $data['borderRadiusBottomLeft'],
            $data['borderRadiusBottomRight'],
            $data['background'],
            $data['eventId'] ?? null,
            array_map(
                fn (array $key): int => $this->irFormatter->getSubId($key['protocol'], $key['address'], $key['command']),
                $data['keys'] ?? []
            ),
        );
    }
}
