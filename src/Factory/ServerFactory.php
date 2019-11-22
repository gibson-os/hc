<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Module\Hc\Service\ServerService as ServerService;

class ServerFactory
{
    /**
     * @param string $protocolName
     *
     * @throws FileNotFound
     *
     * @return ServerService
     */
    public static function create($protocolName)
    {
        $protocol = ProtocolFactory::create($protocolName);
        $server = new ServerService($protocol);

        return $server;
    }
}
