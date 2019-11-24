<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\Formatter\MasterFactory as MasterFormatterFactory;
use GibsonOS\Module\Hc\Repository\Master;
use GibsonOS\Module\Hc\Service\ReceiverService;

class ReceiverFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): ReceiverService
    {
        return new ReceiverService(
            TransformFactory::create(),
            MasterFactory::create(),
            MasterFormatterFactory::create(),
            new Master()
        );
    }

    public static function create(): ReceiverService
    {
        /** @var ReceiverService $service */
        $service = parent::create();

        return $service;
    }
}
