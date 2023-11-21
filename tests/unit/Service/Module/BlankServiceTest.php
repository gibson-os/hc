<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Service\Module;

use Codeception\Test\Unit;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Module\BlankService;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\Test\Unit\Core\ModelManagerTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class BlankServiceTest extends Unit
{
    use ModelManagerTrait;

    private BlankService $blankService;

    private ObjectProphecy|MasterService $masterService;

    private ObjectProphecy|EventService $eventService;

    private ObjectProphecy|ModuleRepository $moduleRepository;

    private ObjectProphecy|TypeRepository $typeRepository;

    private ObjectProphecy|MasterRepository $masterRepository;

    private ObjectProphecy|LogRepository $logRepository;

    private ObjectProphecy|ModuleFactory $slaveFactory;

    protected function _before()
    {
        $this->loadModelManager();

        $this->masterService = $this->prophesize(MasterService::class);
        $this->eventService = $this->prophesize(EventService::class);
        $this->moduleRepository = $this->prophesize(ModuleRepository::class);
        $this->typeRepository = $this->prophesize(TypeRepository::class);
        $this->masterRepository = $this->prophesize(MasterRepository::class);
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->slaveFactory = $this->prophesize(ModuleFactory::class);

        $this->blankService = new BlankService(
            $this->masterService->reveal(),
            new TransformService(),
            $this->eventService->reveal(),
            $this->moduleRepository->reveal(),
            $this->typeRepository->reveal(),
            $this->masterRepository->reveal(),
            $this->logRepository->reveal(),
            $this->slaveFactory->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->modelManager->reveal(),
            $this->modelWrapper->reveal(),
        );
    }

    public function testOnOverwriteExistingSlave(): void
    {
        $slave = $this->prophesize(Module::class);
        $existingSlave = $this->prophesize(Module::class);
        $this->assertEquals(
            $slave->reveal(),
            $this->blankService->onOverwriteExistingSlave($slave->reveal(), $existingSlave->reveal())
        );
    }

    public function testReceive(): void
    {

        $this->blankService->receive(
            $this->prophesize(Module::class)->reveal(),
            (new BusMessage('42.42.42.42', 255))
                ->setSlaveAddress(42)
                ->setData('Handtuch'),
        );
    }
}
