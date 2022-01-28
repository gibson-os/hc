<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Event\AbstractHcEvent;
use GibsonOS\Module\Hc\Model\Module;

class IrService extends AbstractHcSlave
{
    public const COMMAND_SEND = 0;

    public const ATTRIBUTE_TYPE_KEY = 'irKey';

    public const KEY_ATTRIBUTE_NAME = 'name';

    public function slaveHandshake(Module $slave): Module
    {
        return $slave;
    }

    public function onOverwriteExistingSlave(Module $slave, Module $existingSlave): Module
    {
        // @todo Fernbedienungen umschreiben

        return $slave;
    }

    public function receive(Module $slave, BusMessage $busMessage): void
    {
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     *
     * @return $this
     */
    public function sendKey(Module $module, Key $key): IrService
    {
        $this->write(
            $module,
            self::COMMAND_SEND,
            chr($key->getProtocol()) .
            chr($key->getAddress() >> 8) . chr($key->getAddress() & 255) .
            chr($key->getCommand() >> 8) . chr($key->getCommand() & 255)
        );

        return $this;
    }

    protected function getEventClassName(): string
    {
        return AbstractHcEvent::class;
    }
}
