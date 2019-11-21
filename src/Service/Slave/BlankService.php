<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Module\Hc\Model\Module;

class BlankService extends AbstractHcSlave
{
    /**
     * @param Module $slave
     * @param Module $existingSlave
     *
     * @return Module
     */
    public function onOverwriteExistingSlave(Module $slave, Module $existingSlave): Module
    {
        return $slave;
    }

    /**
     * @param Module $slave
     * @param int    $type
     * @param int    $command
     * @param string $data
     */
    public function receive(Module $slave, int $type, int $command, string $data): void
    {
        // TODO: Implement receive() method.
    }
}
