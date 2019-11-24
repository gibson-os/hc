<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Event\Describer;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\Event\Describer\NeopixelService;

class NeopixelFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): NeopixelService
    {
        return new NeopixelService();
    }

    public static function create(): NeopixelService
    {
        /** @var NeopixelService $service */
        $service = parent::create();

        return $service;
    }
}
