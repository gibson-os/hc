<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\Slave\AbstractSlave;

class SlaveFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): SlaveFactory
    {
        return new SlaveFactory();
    }

    public static function create(): SlaveFactory
    {
        /** @var SlaveFactory $service */
        $service = parent::create();

        return $service;
    }

    /**
     * @throws FileNotFound
     */
    public function get(string $serviceName): AbstractSlave
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
