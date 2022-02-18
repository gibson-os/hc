<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Event\AbstractHcEvent;
use GibsonOS\Module\Hc\Model\Module;

class BlankService extends AbstractHcSlave
{
    public function slaveHandshake(Module $module): Module
    {
        return $module;
    }

    public function onOverwriteExistingSlave(Module $module, Module $existingSlave): Module
    {
        return $module;
    }

    public function receive(Module $module, BusMessage $busMessage): void
    {
        // TODO: Implement receive() method.
    }

    protected function getEventClassName(): string
    {
        return AbstractHcEvent::class;
    }
}
