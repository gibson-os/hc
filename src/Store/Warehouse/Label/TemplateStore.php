<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Warehouse\Label;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Warehouse\Label\Template;

/**
 * @extends AbstractDatabaseStore<Template>
 */
class TemplateStore extends AbstractDatabaseStore
{
    protected function getModelClassName(): string
    {
        return Template::class;
    }

    protected function getDefaultOrder(): string
    {
        return '`name`';
    }
}
