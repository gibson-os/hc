<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Event\Describer;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Event\Describer\IoService;

class IoFactory extends AbstractSingletonFactory
{
    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws SelectError
     */
    protected static function createInstance(): IoService
    {
        return new IoService(new TypeRepository());
    }

    public static function create(): IoService
    {
        /** @var IoService $service */
        $service = parent::create();

        return $service;
    }
}
