<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Formatter;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Service\Formatter\MasterFormatter;

class MasterFactory extends AbstractSingletonFactory
{
    protected static function createInstance()
    {
        return new MasterFormatter(TransformFactory::create());
    }

    public static function create(): MasterFormatter
    {
        /** @var MasterFormatter $service */
        $service = parent::create();

        return $service;
    }
}
