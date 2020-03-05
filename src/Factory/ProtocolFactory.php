<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;

class ProtocolFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): ProtocolFactory
    {
        return new ProtocolFactory();
    }

    public static function create(): ProtocolFactory
    {
        /** @var ProtocolFactory $service */
        $service = parent::create();

        return $service;
    }

    /**
     * @param string $protocolName
     *
     * @throws FileNotFound
     */
    public function get($protocolName): ProtocolInterface
    {
        $className = 'GibsonOS\\Module\\Hc\\Factory\\Protocol\\' . ucfirst($protocolName) . 'Factory';

        if (!class_exists($className)) {
            throw new FileNotFound('Protokol ' . $protocolName . ' nicht gefunden!');
        }

        return $className::create();
    }
}
