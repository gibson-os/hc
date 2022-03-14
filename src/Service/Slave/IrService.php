<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Event\AbstractHcEvent;
use GibsonOS\Module\Hc\Event\IrEvent;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Formatter\IrFormatter;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;
use Psr\Log\LoggerInterface;

class IrService extends AbstractHcSlave
{
    public const COMMAND_SEND = 0;

    public const ATTRIBUTE_TYPE_KEY = 'key';

    public const ATTRIBUTE_TYPE_REMOTE = 'remote';

    public const KEY_ATTRIBUTE_NAME = 'name';

    public const REMOTE_ATTRIBUTE_NAME = 'name';

    public const REMOTE_ATTRIBUTE_BACKGROUND = 'background';

    public const REMOTE_ATTRIBUTE_KEYS = 'keys';

    public function __construct(
        MasterService $masterService,
        TransformService $transformService,
        EventService $eventService,
        ModuleRepository $moduleRepository,
        TypeRepository $typeRepository,
        MasterRepository $masterRepository,
        LogRepository $logRepository,
        SlaveFactory $slaveFactory,
        LoggerInterface $logger,
        ModelManager $modelManager,
        private IrFormatter $irFormatter
    ) {
        parent::__construct(
            $masterService,
            $transformService,
            $eventService,
            $moduleRepository,
            $typeRepository,
            $masterRepository,
            $logRepository,
            $slaveFactory,
            $logger,
            $modelManager
        );
    }

    public function slaveHandshake(Module $module): Module
    {
        return $module;
    }

    public function onOverwriteExistingSlave(Module $module, Module $existingSlave): Module
    {
        // @todo Fernbedienungen umschreiben

        return $module;
    }

    public function receive(Module $module, BusMessage $busMessage): void
    {
        foreach ($this->irFormatter->getKeys($busMessage->getData() ?? '') as $key) {
            $this->eventService->fire($this->getEventClassName(), IrEvent::READ_IR, ['slave' => $module, 'key' => $key]);
        }
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

        $this->eventService->fire($this->getEventClassName(), IrEvent::BEFORE_WRITE_IR, ['slave' => $module]);

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

        $this->eventService->fire($this->getEventClassName(), IrEvent::AFTER_WRITE_IR, ['slave' => $module]);

        return $this;
    }

    protected function getEventClassName(): string
    {
        return AbstractHcEvent::class;
    }
}
