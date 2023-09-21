<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Type;

/**
 * @extends AbstractDatabaseStore<Type>
 */
class TypeStore extends AbstractDatabaseStore
{
    protected function getModelClassName(): string
    {
        return Type::class;
    }

    protected function getOrderMapping(): array
    {
        return [
            'id' => '`id`',
            'name' => '`name`',
            'helper' => '`helper`',
            'network' => '`network`',
            'hertz' => '`hertz`',
            'isHcSlave' => '`isHcSlave`',
        ];
    }
}
