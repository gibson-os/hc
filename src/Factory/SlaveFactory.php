<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Service\ServiceManagerService;
use GibsonOS\Module\Hc\Service\Slave\AbstractSlave;

class SlaveFactory
{
    public function __construct(private ServiceManagerService $serviceManagerService)
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
        $slave = $this->serviceManagerService->get($className);

        return $slave;
    }
}
