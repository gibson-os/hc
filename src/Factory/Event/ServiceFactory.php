<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Event;

use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\Event\AbstractEventService;

class ServiceFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): ServiceFactory
    {
        return new ServiceFactory();
    }

    public static function create(): ServiceFactory
    {
        /** @var ServiceFactory $service */
        $service = parent::create();

        return $service;
    }

    /**
     * @throws FileNotFound
     */
    public function get(string $serviceName): AbstractEventService
    {
        $className = 'GibsonOS\\Module\\Hc\\Factory\\Event\\' . ucfirst($serviceName) . 'Factory';

        if (!class_exists($className)) {
            throw new FileNotFound(sprintf('Factory "%s" nicht gefunden!', $className));
        }

        /** @var AbstractSingletonFactory $className */
        /** @var AbstractEventService $service */
        $service = $className::create();

        return $service;
    }
}
