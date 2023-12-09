<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Manager\ServiceManager;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;

class ProtocolFactory
{
    public function __construct(private readonly ServiceManager $serviceManager)
    {
    }

    /**
     * @throws FactoryError
     */
    public function get(string $protocolName): ProtocolInterface
    {
        /** @var class-string $className */
        $className = 'GibsonOS\\Module\\Hc\\Service\\Protocol\\' . ucfirst($protocolName) . 'Service';
        /** @var ProtocolInterface $protocol */
        $protocol = $this->serviceManager->get($className, ProtocolInterface::class);

        return $protocol;
    }
}
