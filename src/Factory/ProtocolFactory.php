<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Service\ServiceManagerService;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;

class ProtocolFactory
{
    private ServiceManagerService $serviceManagerService;

    public function __construct(ServiceManagerService $serviceManagerService)
    {
        $this->serviceManagerService = $serviceManagerService;
    }

    /**
     * @throws FactoryError
     */
    public function get(string $protocolName): ProtocolInterface
    {
        /** @var ProtocolInterface $protocol */
        $protocol = $this->serviceManagerService->get(
            'GibsonOS\\Module\\Hc\\Service\\Protocol\\' . ucfirst($protocolName) . 'Service'
        );

        return $protocol;
    }
}
