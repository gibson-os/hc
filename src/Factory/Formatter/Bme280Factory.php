<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Formatter;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Service\Formatter\Bme280Formatter;

class Bme280Factory extends AbstractSingletonFactory
{
    protected static function createInstance(): Bme280Formatter
    {
        return new Bme280Formatter(TransformFactory::create());
    }

    public static function create(): Bme280Formatter
    {
        /** @var Bme280Formatter $service */
        $service = parent::create();

        return $service;
    }
}
