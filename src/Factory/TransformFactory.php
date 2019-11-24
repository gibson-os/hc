<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\TransformService;

class TransformFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): TransformService
    {
        return new TransformService();
    }

    public static function create(): TransformService
    {
        /** @var TransformService $service */
        $service = parent::create();

        return $service;
    }
}
