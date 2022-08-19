<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Warehouse;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Warehouse\Label;

class LabelStore extends AbstractDatabaseStore
{
    protected function getModelClassName(): string
    {
        return Label::class;
    }

    protected function getDefaultOrder(): string
    {
        return '`name`';
    }
}
