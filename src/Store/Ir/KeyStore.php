<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Ir;

use GibsonOS\Core\Dto\Model\ChildrenMapping;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Ir\Key;
use MDO\Enum\OrderDirection;

/**
 * @extends AbstractDatabaseStore<Key>
 */
class KeyStore extends AbstractDatabaseStore
{
    protected function getModelClassName(): string
    {
        return Key::class;
    }

    protected function getAlias(): ?string
    {
        return 'k';
    }

    protected function getDefaultOrder(): array
    {
        return ['`n`.`name`' => OrderDirection::ASC];
    }

    protected function getExtends(): array
    {
        return [new ChildrenMapping('names', 'name_', 'n')];
    }

    protected function getOrderMapping(): array
    {
        return [
            'name' => '`n`.`name`',
            'protocolName' => '`k`.`protocol`',
            'address' => '`k`.`address`',
            'command' => '`k`.`command`',
        ];
    }
}
