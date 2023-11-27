<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Module;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Event\IrEvent;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Formatter\IrFormatter;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;
use JsonException;
use Psr\Log\LoggerInterface;
use ReflectionException;

class IrService extends AbstractHcModule
{
    public const COMMAND_SEND = 0;

    public function __construct(
        MasterService $masterService,
        TransformService $transformService,
        EventService $eventService,
        ModuleRepository $moduleRepository,
        TypeRepository $typeRepository,
        MasterRepository $masterRepository,
        LogRepository $logRepository,
        ModuleFactory $moduleFactory,
        LoggerInterface $logger,
        ModelManager $modelManager,
        ModelWrapper $modelWrapper,
        private readonly IrFormatter $irFormatter
    ) {
        parent::__construct(
            $masterService,
            $transformService,
            $eventService,
            $moduleRepository,
            $typeRepository,
            $masterRepository,
            $logRepository,
            $moduleFactory,
            $logger,
            $modelManager,
            $modelWrapper,
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
     * @throws WriteException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     */
    public function sendKeys(Module $module, array $keys): IrService
    {
        if (count($keys) === 0) {
            return $this;
        }

        $this->eventService->fire($this->getEventClassName(), IrEvent::BEFORE_WRITE_IR, ['slave' => $module]);

        foreach ($keys as $key) {
            $this->write(
                $module,
                self::COMMAND_SEND,
                chr($key->getProtocol()->value) .
                chr($key->getAddress() >> 8) . chr($key->getAddress() & 255) .
                chr($key->getCommand() >> 8) . chr($key->getCommand() & 255)
            );
            usleep(10);
        }

        $this->eventService->fire($this->getEventClassName(), IrEvent::AFTER_WRITE_IR, ['slave' => $module]);

        return $this;
    }

    protected function getEventClassName(): string
    {
        return IrEvent::class;
    }
}
