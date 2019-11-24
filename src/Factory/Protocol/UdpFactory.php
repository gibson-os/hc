<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Protocol;

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\Protocol\UdpService;

class UdpFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): UdpService
    {
        return new UdpService();
    }

    public static function create(): UdpService
    {
        /** @var UdpService $service */
        $service = parent::create();

        return $service;
    }
}
