<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Formatter;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\TransformFactory;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\Formatter\IoFormatter;

class IoFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): IoFormatter
    {
        return new IoFormatter(TransformFactory::create(), new ValueRepository(), new LogRepository());
    }

    public static function create(): IoFormatter
    {
        /** @var IoFormatter $service */
        $service = parent::create();

        return $service;
    }
}
