<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Model\Module;

class BlankService extends AbstractHcSlave
{
    public function slaveHandshake(Module $slave): Module
    {
        return $slave;
    }

    public function onOverwriteExistingSlave(Module $slave, Module $existingSlave): Module
    {
        return $slave;
    }

    public function receive(Module $slave, BusMessage $busMessage): void
    {
        // TODO: Implement receive() method.
    }
}
