<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\Formatter\MasterFactory;
use GibsonOS\Module\Hc\Repository\Master;
use GibsonOS\Module\Hc\Service\SenderService;

class SenderFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): SenderService
    {
        return new SenderService(MasterFactory::create(), TransformFactory::create(), new Master());
    }

    public static function create(): SenderService
    {
        /** @var SenderService $service */
        $service = parent::create();

        return $service;
    }
}
