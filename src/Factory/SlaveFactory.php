<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\Slave\AbstractSlave;

class SlaveFactory
{
    /**
     * @throws FileNotFound
     * @throws SelectError
     */
    public static function create(string $serviceName): AbstractSlave
    {
        $className = 'GibsonOS\\Module\\Hc\\Factory\\Slave\\' . ucfirst($serviceName) . 'Factory';

        if (!class_exists($className)) {
            throw new FileNotFound(sprintf('Factory "%s" nicht gefunden!', $className));
        }

        /** @var AbstractSingletonFactory $className */
        /** @var AbstractSlave $slave */
        $slave = $className::create();

        return $slave;
    }
}
