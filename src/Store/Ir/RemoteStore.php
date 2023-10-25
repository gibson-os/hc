<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Ir;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Ir\Remote;
use MDO\Enum\OrderDirection;

/**
 * @extends AbstractDatabaseStore<Remote>
 */
class RemoteStore extends AbstractDatabaseStore
{
    protected function getModelClassName(): string
    {
        return Remote::class;
    }

    protected function getDefaultOrder(): array
    {
        return ['`name`' => OrderDirection::ASC];
    }
}
