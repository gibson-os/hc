<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Event\Describer;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\Event\Describer\IoService;

class IoFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): IoService
    {
        return new IoService();
    }

    public static function create(): IoService
    {
        /** @var IoService $service */
        $service = parent::create();

        return $service;
    }
}
