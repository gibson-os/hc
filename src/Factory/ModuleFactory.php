<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Manager\ServiceManager;
use GibsonOS\Module\Hc\Service\Module\AbstractModule;

class ModuleFactory
{
    public function __construct(private readonly ServiceManager $serviceManager)
    {
    }

    /**
     * @throws FactoryError
     */
    public function get(string $serviceName): AbstractModule
    {
        /** @var class-string $className */
        $className = 'GibsonOS\\Module\\Hc\\Service\\Module\\' . ucfirst($serviceName) . 'Service';
        /** @var AbstractModule $slave */
        $slave = $this->serviceManager->get($className);

        return $slave;
    }
}
