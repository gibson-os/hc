<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper\Ir;

use GibsonOS\Module\Hc\Dto\Ir\Key;

class KeyMapper
{
    public function map(array $data): Key
    {
        return new Key(
            $data['protocol'],
            $data['address'],
            $data['command'],
            $data['name'] ?? null,
            $data['protocolName'] ?? null
        );
    }
}
