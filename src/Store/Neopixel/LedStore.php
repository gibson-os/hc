<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Neopixel;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Led;

/**
 * @method \Generator<Led> getList()
 */
class LedStore extends AbstractDatabaseStore
{
    protected Module $module;

    public function setModule(Module $module): void
    {
        $this->module = $module;
    }

    protected function getModelClassName(): string
    {
        return Led::class;
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
