<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Event;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\Event\IoService;

class IoFactory extends AbstractSingletonFactory
{
    protected static function createInstance()
    {
        return new IoService(Describer\IoFactory::create());
    }
}
