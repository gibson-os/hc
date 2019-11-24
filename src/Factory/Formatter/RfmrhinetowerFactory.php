<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Formatter;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Service\Formatter\RfmrhinetowerFormatter;

class RfmrhinetowerFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): RfmrhinetowerFormatter
    {
        return new RfmrhinetowerFormatter(TransformFactory::create());
    }

    public static function create(): RfmrhinetowerFormatter
    {
        /** @var RfmrhinetowerFormatter $service */
        $service = parent::create();

        return $service;
    }
}
