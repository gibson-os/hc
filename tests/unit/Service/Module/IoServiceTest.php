<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Service\Module;

use Codeception\Test\Unit;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Manager\ServiceManager;
use GibsonOS\Core\Service\DevicePushService;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Core\Service\LoggerService;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Mapper\Io\DirectConnectMapper;
use GibsonOS\Module\Hc\Mapper\Io\PortMapper;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
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
use MDO\Client;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class IoServiceTest extends Unit
{
    use ProphecyTrait;
    use ModelManagerTrait;

    private IoService $ioService;

    private ObjectProphecy|MasterService $masterService;

    private TransformService $transformService;

    private EventService $eventService;

    private ObjectProphecy|ModuleRepository $moduleRepository;

    private ObjectProphecy|TypeRepository $typeRepository;

    private ObjectProphecy|MasterRepository $masterRepository;

    private ModuleFactory $moduleFactory;

    private ObjectProphecy|Module $module;

    private ObjectProphecy|LogRepository $logRepository;

    private ObjectProphecy|Master $master;

    private ObjectProphecy|PortRepository $portRepository;

    private ObjectProphecy|DirectConnectRepository $directConnectRepository;

    private ObjectProphecy|DevicePushService $devicePushService;

    private ServiceManager $serviceManager;

    protected function _before(): void
    {
        $this->loadModelManager();
        $this->masterService = $this->prophesize(MasterService::class);
        $this->serviceManager = new ServiceManager();
        $this->serviceManager->setInterface(LoggerInterface::class, LoggerService::class);
        $this->serviceManager->setService(Client::class, $this->client->reveal());
        $this->serviceManager->setService(ModelManager::class, $this->modelManager->reveal());
        $this->transformService = $this->serviceManager->get(TransformService::class);
        $this->eventService = $this->serviceManager->get(EventService::class);
        $this->moduleRepository = $this->prophesize(ModuleRepository::class);
        $this->typeRepository = $this->prophesize(TypeRepository::class);
        $this->masterRepository = $this->prophesize(MasterRepository::class);
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->portRepository = $this->prophesize(PortRepository::class);
        $this->directConnectRepository = $this->prophesize(DirectConnectRepository::class);
        $this->moduleFactory = $this->serviceManager->get(ModuleFactory::class);
        $this->devicePushService = $this->prophesize(DevicePushService::class);
        $this->module = $this->prophesize(Module::class);
        $this->master = $this->prophesize(Master::class);

        $this->ioService = new IoService(
            $this->masterService->reveal(),
            $this->transformService,
            $this->eventService,
            $this->moduleRepository->reveal(),
            $this->typeRepository->reveal(),
            $this->masterRepository->reveal(),
            $this->logRepository->reveal(),
            $this->moduleFactory,
            $this->serviceManager->get(LoggerInterface::class),
            $this->modelManager->reveal(),
            $this->modelWrapper->reveal(),
            $this->serviceManager->get(PortMapper::class),
            $this->serviceManager->get(DirectConnectMapper::class),
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
            'config',
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
            4
        );

        $ports = [
            [
                'direction' => 0,
                'value' => 1,
                'delay' => 100,
                'pullUp' => 1,
            ], [
                'direction' => 1,
                'value' => 0,
                'pwm' => 4,
                'fadeIn' => 0,
                'blink' => 3,
            ],
        ];
        $this->ioFormatter->getPortsAsArray('ports', 2)
            ->shouldBeCalledOnce()
            ->willReturn($ports)
        ;

        $this->prophesizeUpdatePortAttributes(0, 42424242, $ports[0]);
        $this->prophesizeUpdatePortAttributes(1, 42424242, $ports[1]);

        $this->module->getId()
            ->shouldBeCalledTimes(2)
            ->willReturn(42424242)
        ;
        $this->module->getMaster()
            ->shouldBeCalledTimes(6)
            ->willReturn($this->master->reveal())
        ;
        $this->module->getAddress()
            ->shouldBeCalledTimes(6)
            ->willReturn(42)
        ;
        $this->module->getTypeId()
            ->shouldBeCalledTimes(2)
            ->willReturn(7)
        ;
        $this->module->getConfig()
            ->shouldBeCalledTimes(2)
            ->willReturn(null, '2')
        ;
        $this->module->setConfig('2')
            ->shouldBeCalledOnce()
            ->willReturn($this->module->reveal())
        ;
        $this->module->save()
            ->shouldBeCalledOnce()
        ;

        $this->ioService->slaveHandshake($this->module->reveal());
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

        $ports = [
            [
                'direction' => 0,
                'value' => 1,
                'delay' => 100,
                'pullUp' => 1,
            ], [
                'direction' => 1,
                'value' => 0,
                'pwm' => 4,
                'fadeIn' => 0,
                'blink' => 3,
            ],
        ];
        $this->ioFormatter->getPortsAsArray('ports', 2)
            ->shouldBeCalledOnce()
            ->willReturn($ports)
        ;

        $this->prophesizeUpdatePortAttributes(0, 42424242, $ports[0]);
        $this->prophesizeUpdatePortAttributes(1, 42424242, $ports[1]);

        $this->module->getId()
            ->shouldBeCalledTimes(2)
            ->willReturn(42424242)
        ;
        $this->module->getMaster()
            ->shouldBeCalledTimes(3)
            ->willReturn($this->master->reveal())
        ;
        $this->module->getAddress()
            ->shouldBeCalledTimes(3)
            ->willReturn(42)
        ;
        $this->module->getTypeId()
            ->shouldBeCalledTimes(2)
            ->willReturn(7)
        ;
        $this->module->getConfig()
            ->shouldBeCalledTimes(2)
            ->willReturn('2')
        ;

        $this->ioService->slaveHandshake($this->module->reveal());
    }

    public function testSlaveHandshakeException(): void
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

        $ports = [
            [
                'direction' => 0,
                'value' => 1,
                'delay' => 100,
                'pullUp' => 1,
            ], [
                'direction' => 1,
                'value' => 0,
                'pwm' => 4,
                'fadeIn' => 0,
                'blink' => 3,
            ],
        ];
        $this->ioFormatter->getPortsAsArray('ports', 2)
            ->shouldBeCalledOnce()
            ->willReturn($ports)
        ;

        $this->module->getId()
            ->shouldBeCalledOnce()
            ->willReturn(42424242)
        ;
        $this->module->getMaster()
            ->shouldBeCalledTimes(3)
            ->willReturn($this->master->reveal())
        ;
        $this->module->getAddress()
            ->shouldBeCalledTimes(3)
            ->willReturn(42)
        ;
        $this->module->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;
        $this->module->getConfig()
            ->shouldBeCalledTimes(2)
            ->willReturn('2')
        ;

        $this->expectException(GetError::class);
        $this->ioService->slaveHandshake($this->module->reveal());
    }

    public function testSlaveHandshakeNoAttributes(): void
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

        $ports = [
            [
                'direction' => 0,
                'value' => 1,
                'delay' => 100,
                'pullUp' => 1,
            ], [
                'direction' => 1,
                'value' => 0,
                'pwm' => 4,
                'fadeIn' => 0,
                'blink' => 3,
            ],
        ];
        $this->ioFormatter->getPortsAsArray('ports', 2)
            ->shouldBeCalledOnce()
            ->willReturn($ports)
        ;

        $this->prophesizeAddPortAttributes(0, $ports[0]);
        $this->prophesizeAddPortAttributes(1, $ports[1]);

        $this->module->getMaster()
            ->shouldBeCalledTimes(3)
            ->willReturn($this->master->reveal())
        ;
        $this->module->getAddress()
            ->shouldBeCalledTimes(3)
            ->willReturn(42)
        ;
        $this->module->getConfig()
            ->shouldBeCalledTimes(2)
            ->willReturn('2')
        ;

        $this->ioService->slaveHandshake($this->module->reveal());
    }

    public function testOnOverwriteExistingSlave(): void
    {
        $this->assertEquals(
            $this->module->reveal(),
            $this->ioService->onOverwriteExistingSlave(
                $this->module->reveal(),
                $this->prophesize(Module::class)->reveal()
            )
        );
    }

    public function testReceive(): void
    {
        $ports = [
            [
                'direction' => 0,
                'value' => 1,
                'delay' => 100,
                'pullUp' => 1,
            ], [
                'direction' => 1,
                'value' => 0,
                'pwm' => 4,
                'fadeIn' => 0,
                'blink' => 3,
            ],
        ];
        $this->ioFormatter->getPortsAsArray('Handtuch', 2)
            ->shouldBeCalledOnce()
            ->willReturn($ports)
        ;

        $this->prophesizeUpdatePortAttributes(0, 424242, $ports[0]);
        $this->prophesizeUpdatePortAttributes(1, 424242, $ports[1]);

        $this->module->getId()
            ->shouldBeCalledTimes(2)
            ->willReturn(424242)
        ;
        $this->module->getConfig()
            ->shouldBeCalledOnce()
            ->willReturn('2')
        ;
        $this->module->getTypeId()
            ->shouldBeCalledTimes(2)
            ->willReturn(7)
        ;

        $this->ioService->receive($this->module->reveal(), 255, 42, 'Handtuch');
    }

    public function testReadPort(): void
    {
        $port = [
            'direction' => 0,
            'value' => 1,
            'delay' => 100,
            'pullUp' => 1,
        ];

        $this->eventService->fire('', 'beforeReadPort', ['slave' => $this->module->reveal(), 'number' => 32])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire(
            '',
            'afterReadPort',
            array_merge(['slave' => $this->module->reveal(), 'number' => 32], $port)
        )
            ->shouldBeCalledOnce()
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
        $this->ioFormatter->getPortAsArray('Handtuch')
            ->shouldBeCalledOnce()
            ->willReturn($port)
        ;
        $this->prophesizeUpdatePortAttributes(32, 424242, $port);

        $this->ioService->readPort($this->module->reveal(), 32);
    }

    public function testReadPortsFromEeprom(): void
    {
        $this->eventService->fire('', 'beforeReadPortsFromEeprom', ['slave' => $this->module->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterReadPortsFromEeprom', ['slave' => $this->module->reveal()])
            ->shouldBeCalledOnce()
        ;

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

        $this->ioService->readPortsFromEeprom($this->module->reveal());
    }

    public function testReadPortsFromEepromNoData(): void
    {
        $this->eventService->fire('', 'beforeReadPortsFromEeprom', ['slave' => $this->module->reveal()])
            ->shouldBeCalledOnce()
        ;

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

        $this->expectException(ReceiveError::class);
        $this->ioService->readPortsFromEeprom($this->module->reveal());
    }

    public function testWritePortsToEeprom(): void
    {
        $this->eventService->fire('', 'beforeWritePortsToEeprom', ['slave' => $this->module->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWritePortsToEeprom', ['slave' => $this->module->reveal()])
            ->shouldBeCalledOnce()
        ;

        AbstractModuleTest::prophesizeWrite(
            $this->master,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            135,
            'aa'
        );

        $this->ioService->writePortsToEeprom($this->module->reveal());
    }

    /**
     * @dataProvider getToggleValueData
     */
    public function testToggleValueOn(string $returnValue, string $setValue): void
    {
        $value = $this->mockValueModel('value', '0', '1');
        $value->getValue()
            ->shouldBeCalledTimes(2)
            ->willReturn('0', '1')
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
        $this->ioFormatter->getPortAsString(['value' => '1'])
            ->shouldBeCalledOnce()
            ->willReturn('Handtuch')
        ;

        $this->eventService->fire('beforeWritePort', ['slave' => $this->module->reveal(), 'number' => 32, 'value' => '1']);
        $this->eventService->fire('afterWritePort', ['slave' => $this->module->reveal(), 'number' => 32, 'value' => '1']);

        $this->module->getId()
            ->shouldBeCalledOnce()
            ->willReturn(424242)
        ;
        $this->module->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(42)
        ;

        $this->ioService->toggleValue($this->module->reveal(), 32);
    }

    private function prophesizeAddPortAttributes(int $number, array $data): void
    {
    }

    private function prophesizeUpdatePortAttributes(int $number, int $slaveId, array $data): void
    {
        $values = [];

        foreach ($data as $key => $value) {
            $value = (string) $value;
            $values[] = $this->mockValueModel($key, $key === 'value' ? $value === '0' ? '1' : '0' : $value, $value)->reveal();
        }

        $this->module->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;
        $this->module->getId()
            ->shouldBeCalledOnce()
            ->willReturn($slaveId)
        ;
    }

    /**
     * @return ObjectProphecy|Value
     */
    private function mockValueModel(string $key, string $returnValue, string $setValue, int $order = 0): ObjectProphecy
    {
        /** @var Attribute|ObjectProphecy $attribute */
        $attribute = $this->prophesize(Attribute::class);
        $attribute->getKey()
            ->shouldBeCalledOnce()
            ->willReturn($key)
        ;
        /** @var Value|ObjectProphecy $value */
        $value = $this->prophesize(Value::class);
        $value->getAttribute()
            ->shouldBeCalledOnce()
            ->willReturn($attribute->reveal())
        ;
        $value->getValue()
            ->shouldBeCalledOnce()
            ->willReturn($returnValue)
        ;
        $value->getOrder()
            ->shouldBeCalledTimes($key === 'valueName' ? 1 : 0)
            ->willReturn($order)
        ;
        $value->setValue($setValue)
            ->shouldBeCalledTimes($returnValue === $setValue ? 0 : 1)
            ->willReturn($value->reveal())
        ;
        $value->save()
            ->shouldBeCalledTimes($returnValue === $setValue ? 0 : 1)
        ;

        return $value;
    }

    public function getToggleValueData(): array
    {
        return [
            ['0', '1'],
            ['1', '0'],
        ];
    }
}
