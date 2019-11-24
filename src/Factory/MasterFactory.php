<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Repository\Module;
use GibsonOS\Module\Hc\Repository\Type;
use GibsonOS\Module\Hc\Service\MasterService;

class MasterFactory extends AbstractSingletonFactory
{
    /**
     * @throws FileNotFound
     */
    protected static function createInstance(): MasterService
    {
        $master = new MasterService(
            SenderFactory::create(),
            EventFactory::create(),
            TransformFactory::create(),
            new Module(),
            new Type()
        );

        return $master;
    }

    public static function create(): MasterService
    {
        /** @var MasterService $service */
        $service = parent::create();

        return $service;
    }
}
