<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Io;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;

class PortStore extends AbstractDatabaseStore
{
    protected Module $module;

    public function setModule(Module $module): void
    {
        $this->module = $module;
    }

    protected function getModelClassName(): string
    {
        return Port::class;
    }

    protected function setWheres(): void
    {
        $this->addWhere('`module_id`=?', [$this->module->getId()]);
    }

    protected function getDefaultOrder(): string
    {
        return '`number`';
    }
}
