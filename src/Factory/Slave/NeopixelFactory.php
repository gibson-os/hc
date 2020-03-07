<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Slave;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\Attribute\Neopixel\LedFactory;
use GibsonOS\Module\Hc\Factory\EventFactory;
use GibsonOS\Module\Hc\Factory\Formatter\NeopixelFactory as NeopixelFormatterFactory;
use GibsonOS\Module\Hc\Factory\MasterFactory;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;

class NeopixelFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): NeopixelService
    {
        return new NeopixelService(
            MasterFactory::create(),
            TransformFactory::create(),
            EventFactory::create(),
            NeopixelFormatterFactory::create(),
            LedFactory::create(),
            new ModuleRepository(),
            new TypeRepository(),
            new MasterRepository(),
            new LogRepository(),
            SlaveFactory::create()
        );
    }

    public static function create(): NeopixelService
    {
        /** @var NeopixelService $service */
        $service = parent::create();

        return $service;
    }
}
