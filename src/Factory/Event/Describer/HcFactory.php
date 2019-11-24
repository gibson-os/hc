<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Event\Describer;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\Event\Describer\HcService;

class HcFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): HcService
    {
        return new HcService();
    }

    public static function create(): HcService
    {
        /** @var HcService $service */
        $service = parent::create();

        return $service;
    }
}
