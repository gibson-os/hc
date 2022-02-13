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

    public const ATTRIBUTE_TYPE_KEY = 'key';

    public const ATTRIBUTE_TYPE_REMOTE = 'remote';

    public const KEY_ATTRIBUTE_NAME = 'name';

    public const REMOTE_ATTRIBUTE_NAME = 'name';

    public const REMOTE_ATTRIBUTE_BACKGROUND = 'background';

    public const REMOTE_ATTRIBUTE_KEYS = 'keys';

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
     * @param Key[] $keys
     *
     * @throws AbstractException
     * @throws SaveError
     *
     * @return $this
     */
    public function sendKeys(Module $module, array $keys): IrService
    {
        if (count($keys) === 0) {
            return $this;
        }

        $data = '';
        $i = 0;

        foreach ($keys as $key) {
            if ($i === 6) {
                $this->write($module, self::COMMAND_SEND, $data);
                $data = '';
            }

            $data .=
                chr($key->getProtocol()) .
                chr($key->getAddress() >> 8) . chr($key->getAddress() & 255) .
                chr($key->getCommand() >> 8) . chr($key->getCommand() & 255)
            ;
            ++$i;
        }

        $this->write($module, self::COMMAND_SEND, $data);

        return $this;
    }

    protected function getEventClassName(): string
    {
        return AbstractHcEvent::class;
    }
}
