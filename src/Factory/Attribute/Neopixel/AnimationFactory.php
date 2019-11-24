<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Attribute\Neopixel;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Repository\Attribute as AttributeRepository;
use GibsonOS\Module\Hc\Repository\Attribute\Value as ValueRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\AnimationService;

class AnimationFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): AnimationService
    {
        return new AnimationService(new AttributeRepository(), new ValueRepository());
    }

    public static function create(): AnimationService
    {
        /** @var AnimationService $service */
        $service = parent::create();

        return $service;
    }
}
