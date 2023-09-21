<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Warehouse;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Warehouse\Box;

/**
 * @extends AbstractDatabaseStore<Box>
 */
class BoxStore extends AbstractDatabaseStore
{
    private Module $module;

    protected function getModelClassName(): string
    {
        return Box::class;
    }

    protected function setWheres(): void
    {
        $this->addWhere('`module_id`=?', [$this->module->getId()]);
    }

    public function setModule(Module $module): BoxStore
    {
        $this->module = $module;

        return $this;
    }
}
