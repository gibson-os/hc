<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Formatter;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Service\Formatter\BlankFormatter;

class BlankFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): BlankFormatter
    {
        return new BlankFormatter(TransformFactory::create());
    }

    public static function create(): BlankFormatter
    {
        /** @var BlankFormatter $service */
        $service = parent::create();

        return $service;
    }
}
