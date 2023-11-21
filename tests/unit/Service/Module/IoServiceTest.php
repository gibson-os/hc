<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Service\Module;

use Codeception\Test\Unit;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\DevicePushService;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Dto\Io\Direction;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Mapper\Io\DirectConnectMapper;
use GibsonOS\Module\Hc\Mapper\Io\PortMapper;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\Io\DirectConnectRepository;
use GibsonOS\Module\Hc\Repository\Io\PortRepository;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Module\IoService;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\Test\Unit\Core\ModelManagerTrait;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class IoServiceTest extends Unit
{
    use ProphecyTrait;
    use ModelManagerTrait;

    private IoService $ioService;

    private ObjectProphecy|MasterService $masterService;

    private ObjectProphecy|EventService $eventService;

    private ObjectProphecy|ModuleRepository $moduleRepository;

    private ObjectProphecy|TypeRepository $typeRepository;

    private ObjectProphecy|MasterRepository $masterRepository;

    private ObjectProphecy|ModuleFactory $moduleFactory;

    private Module $module;

    private ObjectProphecy|LogRepository $logRepository;

    private Master $master;

    private ObjectProphecy|PortRepository $portRepository;

    private ObjectProphecy|DirectConnectRepository $directConnectRepository;

    private ObjectProphecy|DevicePushService $devicePushService;

    private ObjectProphecy|PortMapper $portMapper;

    protected function _before(): void
    {
        $this->loadModelManager();

        $this->masterService = $this->prophesize(MasterService::class);
        $this->eventService = $this->prophesize(EventService::class);
        $this->moduleRepository = $this->prophesize(ModuleRepository::class);
        $this->typeRepository = $this->prophesize(TypeRepository::class);
        $this->masterRepository = $this->prophesize(MasterRepository::class);
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->portRepository = $this->prophesize(PortRepository::class);
        $this->directConnectRepository = $this->prophesize(DirectConnectRepository::class);
        $this->moduleFactory = $this->prophesize(ModuleFactory::class);
        $this->devicePushService = $this->prophesize(DevicePushService::class);
        $this->portMapper = $this->prophesize(PortMapper::class);
        $this->master = (new Master($this->modelWrapper->reveal()))
            ->setId(1)
            ->setAddress('42.42.42.42')
            ->setSendPort(420042)
        ;
        $type = (new Type($this->modelWrapper->reveal()))
            ->setId(7)
            ->setHelper('prefect')
        ;
        $this->module = (new Module($this->modelWrapper->reveal()))
            ->setAddress(42)
            ->setDeviceId(4242)
            ->setId(7)
            ->setType($type)
            ->setMaster($this->master)
        ;

        $this->ioService = new IoService(
            $this->masterService->reveal(),
            new TransformService(),
            $this->eventService->reveal(),
            $this->moduleRepository->reveal(),
            $this->typeRepository->reveal(),
            $this->masterRepository->reveal(),
            $this->logRepository->reveal(),
            $this->moduleFactory->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->modelManager->reveal(),
            $this->modelWrapper->reveal(),
            $this->portMapper->reveal(),
            $this->prophesize(DirectConnectMapper::class)->reveal(),
            $this->devicePushService->reveal(),
            $this->portRepository->reveal(),
            $this->directConnectRepository->reveal(),
        );
    }

    public function testSlaveHandshakeEmptyConfig(): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            210,
            '2',
            1
        );
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            250,
            'ports',
            100,
        );

        $this->portMapper->getPorts($this->module, 'ports')
            ->shouldBeCalledOnce()
            ->willReturn([
                (new Port($this->modelWrapper->reveal()))
                    ->setDirection(Direction::INPUT)
                    ->setValue(true)
                    ->setDelay(100)
                    ->setPullUp(true),
            ], [
                (new Port($this->modelWrapper->reveal()))
                    ->setDirection(Direction::OUTPUT)
                    ->setValue(false)
                    ->setPwm(4)
                    ->setFadeIn(0)
                    ->setBlink(3),
            ])
        ;

        $this->assertNull($this->module->getConfig());
        $this->ioService->slaveHandshake($this->module);
        $this->assertEquals(50, $this->module->getConfig());
    }

    public function testSlaveHandshakeExistingConfig(): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            250,
            'ports',
            4
        );
        $this->portMapper->getPorts($this->module, 'ports')
            ->shouldBeCalledOnce()
            ->willReturn([
                (new Port($this->modelWrapper->reveal()))
                    ->setDirection(Direction::INPUT)
                    ->setValue(true)
                    ->setDelay(100)
                    ->setPullUp(true),
            ], [
                (new Port($this->modelWrapper->reveal()))
                    ->setDirection(Direction::OUTPUT)
                    ->setValue(false)
                    ->setPwm(4)
                    ->setFadeIn(0)
                    ->setBlink(3),
            ])
        ;
        $this->module->setConfig('2');

        $this->assertEquals('2', $this->module->getConfig());
        $this->ioService->slaveHandshake($this->module);
        $this->assertEquals('2', $this->module->getConfig());
    }

    public function testOnOverwriteExistingSlave(): void
    {
        $this->assertEquals(
            $this->module,
            $this->ioService->onOverwriteExistingSlave(
                $this->module,
                $this->prophesize(Module::class)->reveal()
            )
        );
    }

    public function testReceive(): void
    {
        $this->portMapper->getPorts($this->module, 'Handtuch')
            ->shouldBeCalledOnce()
            ->willReturn([
                (new Port($this->modelWrapper->reveal()))
                    ->setName('arthur')
                    ->setNumber(1)
                    ->setDirection(Direction::INPUT)
                    ->setValue(true)
                    ->setDelay(100)
                    ->setPullUp(true),
            ], [
                (new Port($this->modelWrapper->reveal()))
                    ->setName('dent')
                    ->setNumber(2)
                    ->setDirection(Direction::OUTPUT)
                    ->setValue(false)
                    ->setPwm(4)
                    ->setFadeIn(0)
                    ->setBlink(3),
            ])
        ;

        $this->ioService->receive(
            $this->module,
            (new BusMessage('42.42.42.42', 255))
                ->setSlaveAddress(42)
                ->setData('Handtuch')
        );
    }

    public function testReadPort(): void
    {
        $port = (new Port($this->modelWrapper->reveal()))
            ->setName('arthur')
            ->setModule($this->module)
            ->setNumber(32)
        ;

        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            32,
            'Handtuch',
            2
        );

        $newPort = (new Port($this->modelWrapper->reveal()))
            ->setDirection(Direction::INPUT)
            ->setValue(true)
            ->setDelay(100)
            ->setPullUp(true)
        ;
        $this->portMapper->getPort($port, 'Handtuch')
            ->shouldBeCalledOnce()
            ->willReturn($newPort)
        ;

        $this->assertEquals($newPort, $this->ioService->readPort($port));
    }

    public function testReadPortsFromEeprom(): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            135,
            'Handtuch',
            1
        );

        $this->ioService->readPortsFromEeprom($this->module);
    }

    public function testReadPortsFromEepromNoData(): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            135,
            '',
            1
        );

        $this->expectException(ReceiveError::class);
        $this->ioService->readPortsFromEeprom($this->module);
    }

    public function testWritePortsToEeprom(): void
    {
        $deviceId = $this->module->getDeviceId();
        AbstractModuleTest::prophesizeWrite(
            $this->master,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            135,
            chr($deviceId >> 8) . chr($deviceId & 255)
        );

        $this->ioService->writePortsToEeprom($this->module);
    }

    /**
     * @dataProvider getToggleValueData
     */
    public function testToggleValueOn(string $returnValue, string $setValue): void
    {
        $port = (new Port($this->modelWrapper->reveal()))
            ->setName('Marvin')
            ->setNumber(32)
            ->setValue(false)
            ->setModule($this->module)
        ;

        AbstractModuleTest::prophesizeWrite(
            $this->master,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            32,
            'Handtuch'
        );
        $this->portMapper->getPortAsString($port)
            ->shouldBeCalledOnce()
            ->willReturn('Handtuch')
        ;

        $this->ioService->toggleValue($port);
    }

    public function getToggleValueData(): array
    {
        return [
            ['0', '1'],
            ['1', '0'],
        ];
    }
}
