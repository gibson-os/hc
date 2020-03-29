<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\Formatter\MasterFactory as MasterFormatterFactory;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\MasterService;

class MasterFactory extends AbstractSingletonFactory
{
    /**
     * @throws FileNotFound
     */
    protected static function createInstance(): MasterService
    {
        return new MasterService(
            SenderFactory::create(),
            EventFactory::create(),
            TransformFactory::create(),
            SlaveFactory::create(),
            MasterFormatterFactory::create(),
            new LogRepository(),
            new ModuleRepository(),
            new TypeRepository()
        );
    }

    public static function create(): MasterService
    {
        /** @var MasterService $service */
        $service = parent::create();

        return $service;
    }
}
