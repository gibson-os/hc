<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Formatter;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Service\Formatter\NeopixelFormatter;

class NeopixelFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): NeopixelFormatter
    {
        return new NeopixelFormatter(TransformFactory::create());
    }

    public static function create(): NeopixelFormatter
    {
        /** @var NeopixelFormatter $service */
        $service = parent::create();

        return $service;
    }
}
