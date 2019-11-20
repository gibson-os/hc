<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Module\Hc\Service\Protocol\AbstractProtocol;

class Protocol
{
    /**
     * @param string $protocolName
     *
     * @throws FileNotFound
     *
     * @return AbstractProtocol
     */
    public static function create($protocolName)
    {
        $className = 'GibsonOS\\Module\\Hc\\Service\\Protocol\\' . ucfirst($protocolName);

        if (!class_exists($className)) {
            throw new FileNotFound('Protokoll ' . $protocolName . ' nicht gefunden!');
        }

        return new $className();
    }
}
