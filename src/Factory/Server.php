<?php
namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Module\Hc\Service\Server as ServerService;

class Server
{
    /**
     * @param string $protocolName
     * @return ServerService
     * @throws FileNotFound
     */
    public static function create($protocolName)
    {
        $protocol = Protocol::create($protocolName);
        $server = new ServerService($protocol);

        return $server;
    }
}