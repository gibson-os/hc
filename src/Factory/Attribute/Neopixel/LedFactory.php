<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Attribute\Neopixel;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository as ValueRepository;
use GibsonOS\Module\Hc\Repository\AttributeRepository as AttributeRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService;

class LedFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): LedService
    {
        return new LedService(new AttributeRepository(), new ValueRepository());
    }

    public static function create(): LedService
    {
        /** @var LedService $service */
        $service = parent::create();

        return $service;
    }
}
