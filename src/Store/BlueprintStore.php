<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Blueprint;
use MDO\Enum\OrderDirection;

/**
 * @extends AbstractDatabaseStore<Blueprint>
 */
class BlueprintStore extends AbstractDatabaseStore
{
    protected function getModelClassName(): string
    {
        return Blueprint::class;
    }

    protected function getDefaultOrder(): array
    {
        return ['name' => OrderDirection::ASC];
    }
}
