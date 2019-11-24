<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\EventService;

class EventFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): EventService
    {
        return new EventService();
    }

    public static function create(): EventService
    {
        /** @var EventService $service */
        $service = parent::create();

        return $service;
    }
}
