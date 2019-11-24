<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Event\Describer;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\Event\Describer\TimeService;

class TimeFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): TimeService
    {
        return new TimeService();
    }

    public static function create(): TimeService
    {
        /** @var TimeService $service */
        $service = parent::create();

        return $service;
    }
}
