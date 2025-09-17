<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Master;
use MDO\Enum\OrderDirection;

/**
 * @extends AbstractDatabaseStore<Master>
 */
class MasterStore extends AbstractDatabaseStore
{
    protected function getModelClassName(): string
    {
        return Master::class;
    }

    /**
     * @return array<string, string>
     */
    protected function getOrderMapping(): array
    {
        return [
            'name' => '`name`',
            'protocol' => '`protocol`',
            'address' => '`address`',
            'added' => '`added`',
            'modified' => '`modified`',
        ];
    }

    protected function getDefaultOrder(): array
    {
        return ['`name`' => OrderDirection::ASC];
    }
}
