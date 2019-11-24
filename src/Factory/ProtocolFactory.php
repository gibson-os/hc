<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;

class ProtocolFactory
{
    /**
     * @param string $protocolName
     *
     * @throws FileNotFound
     */
    public static function create($protocolName): ProtocolInterface
    {
        $className = 'GibsonOS\\Module\\Hc\\Factory\\Protocol\\' . ucfirst($protocolName) . 'Factory';

        if (!class_exists($className)) {
            throw new FileNotFound('Protokoll ' . $protocolName . ' nicht gefunden!');
        }

        return new $className();
    }
}
