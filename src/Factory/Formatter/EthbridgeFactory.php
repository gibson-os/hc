<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Formatter;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Core\Factory\ModuleSettingFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Service\Formatter\EthbridgeFormatter;

class EthbridgeFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): EthbridgeFormatter
    {
        return new EthbridgeFormatter(TransformFactory::create(), ModuleSettingFactory::create());
    }

    public static function create(): EthbridgeFormatter
    {
        /** @var EthbridgeFormatter $service */
        $service = parent::create();

        return $service;
    }
}
