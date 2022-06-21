<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Ir;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Ir\Key;

class KeyStore extends AbstractDatabaseStore
{
    protected function getModelClassName(): string
    {
        return Key::class;
    }

    protected function getDefaultOrder(): string
    {
        return '`name`';
    }

    protected function getOrderMapping(): array
    {
        return [
            'name' => 'name',
            'protocolName' => 'protocol',
            'address' => 'address',
            'command' => 'command',
        ];
    }
}
