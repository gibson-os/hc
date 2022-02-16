<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Manager\ServiceManager;
use GibsonOS\Module\Hc\Service\Slave\AbstractSlave;

class SlaveFactory
{
    public function __construct(private ServiceManager $serviceManager)
    {
    }

    /**
     * @throws FactoryError
     */
    public function get(string $serviceName): AbstractSlave
    {
        /** @var class-string $className */
        $className = 'GibsonOS\\Module\\Hc\\Service\\Slave\\' . ucfirst($serviceName) . 'Service';
        /** @var AbstractSlave $slave */
        $slave = $this->serviceManager->get($className);

        return $slave;
    }
}
