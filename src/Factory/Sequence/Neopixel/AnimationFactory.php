<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Sequence\Neopixel;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\AnimationService;

class AnimationFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): AnimationService
    {
        return new AnimationService();
    }

    public static function create(): AnimationService
    {
        /** @var AnimationService $service */
        $service = parent::create();

        return $service;
    }
}
