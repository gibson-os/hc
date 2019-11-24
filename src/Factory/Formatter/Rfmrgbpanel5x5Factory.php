<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Formatter;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Service\Formatter\Rfmrgbpanel5X5Formatter;

class Rfmrgbpanel5x5Factory extends AbstractSingletonFactory
{
    protected static function createInstance(): Rfmrgbpanel5X5Formatter
    {
        return new Rfmrgbpanel5X5Formatter(TransformFactory::create());
    }

    public static function create(): Rfmrgbpanel5X5Formatter
    {
        /** @var Rfmrgbpanel5X5Formatter $service */
        $service = parent::create();

        return $service;
    }
}
