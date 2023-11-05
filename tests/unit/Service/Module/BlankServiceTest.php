<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Service\Module;

use Codeception\Test\Unit;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Module\BlankService;
use GibsonOS\Module\Hc\Service\TransformService;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class BlankServiceTest extends Unit
{
    use ProphecyTrait;

    /**
     * @var BlankService
     */
    private $blankService;

    /**
     * @var ObjectProphecy|MasterService
     */
    private $masterService;

    /**
     * @var TransformService
     */
    private $transformService;

    /**
     * @var ObjectProphecy|EventService
     */
    private $eventService;

    /**
     * @var ObjectProphecy|ModuleRepository
     */
    private $moduleRepository;

    /**
     * @var ObjectProphecy|TypeRepository
     */
    private $typeRepository;

    /**
     * @var ObjectProphecy|MasterRepository
     */
    private $masterRepository;

    /**
     * @var ObjectProphecy|LogRepository
     */
    private $logRepository;

    /**
     * @var ObjectProphecy|ModuleFactory
     */
    private $slaveFactory;

    protected function _before()
    {
        $this->masterService = $this->prophesize(MasterService::class);
        $this->transformService = new TransformService();
        $this->eventService = $this->prophesize(EventService::class);
        $this->moduleRepository = $this->prophesize(ModuleRepository::class);
        $this->typeRepository = $this->prophesize(TypeRepository::class);
        $this->masterRepository = $this->prophesize(MasterRepository::class);
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->slaveFactory = $this->prophesize(ModuleFactory::class);

        $this->blankService = new BlankService(
            $this->masterService->reveal(),
            $this->transformService,
            $this->eventService->reveal(),
            $this->moduleRepository->reveal(),
            $this->typeRepository->reveal(),
            $this->masterRepository->reveal(),
            $this->logRepository->reveal(),
            $this->slaveFactory->reveal()
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
            255,
            42,
            'Handtuch'
        );
    }
}
