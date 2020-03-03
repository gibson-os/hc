<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Slave;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\EventFactory;
use GibsonOS\Module\Hc\Factory\MasterFactory;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\BlankService;

class BlankFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): BlankService
    {
        return new BlankService(
            MasterFactory::create(),
            TransformFactory::create(),
            EventFactory::create(),
            new ModuleRepository(),
            new TypeRepository(),
            SlaveFactory::create()
        );
    }

    public static function create(): BlankService
    {
        /** @var BlankService $service */
        $service = parent::create();

        return $service;
    }
}
