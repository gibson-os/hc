<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Formatter;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Service\Formatter\RfmbellFormatter;

class RfmbellFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): RfmbellFormatter
    {
        return new RfmbellFormatter(TransformFactory::create());
    }

    public static function create(): RfmbellFormatter
    {
        /** @var RfmbellFormatter $service */
        $service = parent::create();

        return $service;
    }
}
