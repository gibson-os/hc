<?php
namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Module\Hc\Model\Module;

class Blank extends AbstractHcSlave
{
    /**
     * @param Module $existingSlave
     */
    public function onOverwriteExistingSlave(Module $existingSlave): void
    {
    }
}