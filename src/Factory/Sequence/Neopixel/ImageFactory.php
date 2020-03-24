<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Sequence\Neopixel;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Repository\Sequence\ElementRepository;
use GibsonOS\Module\Hc\Repository\SequenceRepository;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\ImageService;

class ImageFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): ImageService
    {
        return new ImageService(new SequenceRepository(), new ElementRepository());
    }

    public static function create(): ImageService
    {
        /** @var ImageService $service */
        $service = parent::create();

        return $service;
    }
}
