<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Formatter;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Service\Formatter\Rfmboxvario33Formatter;

class Rfmboxvario33Factory extends AbstractSingletonFactory
{
    protected static function createInstance(): Rfmboxvario33Formatter
    {
        return new Rfmboxvario33Formatter(TransformFactory::create());
    }

    public static function create(): Rfmboxvario33Formatter
    {
        /** @var Rfmboxvario33Formatter $service */
        $service = parent::create();

        return $service;
    }
}
