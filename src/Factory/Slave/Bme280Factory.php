<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Slave;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\Formatter\Bme280Factory as Bme280FormatterFactory;
use GibsonOS\Module\Hc\Factory\MasterFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Service\Slave\Bme280Service;

class Bme280Factory extends AbstractSingletonFactory
{
    protected static function createInstance(): Bme280Service
    {
        return new Bme280Service(MasterFactory::create(), TransformFactory::create(), Bme280FormatterFactory::create());
    }

    public static function create(): Bme280Service
    {
        /** @var Bme280Service $service */
        $service = parent::create();

        return $service;
    }
}
