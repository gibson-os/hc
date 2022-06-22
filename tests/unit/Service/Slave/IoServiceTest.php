<?php
declare(strict_types=1);

namespace Gibson\Test\Unit\Service\Slave;

use Codeception\Test\Unit;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Formatter\IoFormatter;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use GibsonOS\Module\Hc\Service\TransformService;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class IoServiceTest extends Unit
{
    use ProphecyTrait;

    /**
     * @var IoService
     */
    private $ioService;

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
     * @var ObjectProphecy|SlaveFactory
     */
    private $slaveFactory;

    /**
     * @var ObjectProphecy|Module
     */
    private $slave;

    /**
     * @var ObjectProphecy|LogRepository
     */
    private $logRepository;

    /**
     * @var ObjectProphecy|Master
     */
    private $master;

    /**
     * @var ObjectProphecy|IoFormatter
     */
    private $ioFormatter;

    protected function _before(): void
    {
        $this->masterService = $this->prophesize(MasterService::class);
        $this->transformService = new TransformService();
        $this->eventService = $this->prophesize(EventService::class);
        $this->moduleRepository = $this->prophesize(ModuleRepository::class);
        $this->typeRepository = $this->prophesize(TypeRepository::class);
        $this->masterRepository = $this->prophesize(MasterRepository::class);
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->slaveFactory = $this->prophesize(SlaveFactory::class);
        $this->ioFormatter = $this->prophesize(IoFormatter::class);
        $this->slave = $this->prophesize(Module::class);
        $this->master = $this->prophesize(Master::class);

        $this->ioService = new IoService(
            $this->masterService->reveal(),
            $this->transformService,
            $this->eventService->reveal(),
            $this->ioFormatter->reveal(),
            $this->moduleRepository->reveal(),
            $this->typeRepository->reveal(),
            $this->masterRepository->reveal(),
            $this->logRepository->reveal(),
            $this->slaveFactory->reveal(),
        );
    }

    public function testSlaveHandshakeEmptyConfig(): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            210,
            'config',
            1
        );
        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
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

        $this->slave->getId()
            ->shouldBeCalledTimes(2)
            ->willReturn(42424242)
        ;
        $this->slave->getMaster()
            ->shouldBeCalledTimes(6)
            ->willReturn($this->master->reveal())
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(6)
            ->willReturn(42)
        ;
        $this->slave->getTypeId()
            ->shouldBeCalledTimes(2)
            ->willReturn(7)
        ;
        $this->slave->getConfig()
            ->shouldBeCalledTimes(2)
            ->willReturn(null, '2')
        ;
        $this->slave->setConfig('2')
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;
        $this->slave->save()
            ->shouldBeCalledOnce()
        ;

        $this->ioService->slaveHandshake($this->slave->reveal());
    }

    public function testSlaveHandshakeExistingConfig(): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
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

        $this->slave->getId()
            ->shouldBeCalledTimes(2)
            ->willReturn(42424242)
        ;
        $this->slave->getMaster()
            ->shouldBeCalledTimes(3)
            ->willReturn($this->master->reveal())
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(3)
            ->willReturn(42)
        ;
        $this->slave->getTypeId()
            ->shouldBeCalledTimes(2)
            ->willReturn(7)
        ;
        $this->slave->getConfig()
            ->shouldBeCalledTimes(2)
            ->willReturn('2')
        ;

        $this->ioService->slaveHandshake($this->slave->reveal());
    }

    public function testSlaveHandshakeException(): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
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

        $this->slave->getId()
            ->shouldBeCalledOnce()
            ->willReturn(42424242)
        ;
        $this->slave->getMaster()
            ->shouldBeCalledTimes(3)
            ->willReturn($this->master->reveal())
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(3)
            ->willReturn(42)
        ;
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;
        $this->slave->getConfig()
            ->shouldBeCalledTimes(2)
            ->willReturn('2')
        ;

        $this->expectException(GetError::class);
        $this->ioService->slaveHandshake($this->slave->reveal());
    }

    public function testSlaveHandshakeNoAttributes(): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
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

        $this->slave->getMaster()
            ->shouldBeCalledTimes(3)
            ->willReturn($this->master->reveal())
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(3)
            ->willReturn(42)
        ;
        $this->slave->getConfig()
            ->shouldBeCalledTimes(2)
            ->willReturn('2')
        ;

        $this->ioService->slaveHandshake($this->slave->reveal());
    }

    public function testOnOverwriteExistingSlave(): void
    {
        $this->assertEquals(
            $this->slave->reveal(),
            $this->ioService->onOverwriteExistingSlave(
                $this->slave->reveal(),
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

        $this->slave->getId()
            ->shouldBeCalledTimes(2)
            ->willReturn(424242)
        ;
        $this->slave->getConfig()
            ->shouldBeCalledOnce()
            ->willReturn('2')
        ;
        $this->slave->getTypeId()
            ->shouldBeCalledTimes(2)
            ->willReturn(7)
        ;

        $this->ioService->receive($this->slave->reveal(), 255, 42, 'Handtuch');
    }

    public function testReadPort(): void
    {
        $port = [
            'direction' => 0,
            'value' => 1,
            'delay' => 100,
            'pullUp' => 1,
        ];

        $this->eventService->fire('beforeReadPort', ['slave' => $this->slave->reveal(), 'number' => 32])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire(
            'afterReadPort',
            array_merge(['slave' => $this->slave->reveal(), 'number' => 32], $port)
        )
            ->shouldBeCalledOnce()
        ;

        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
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

        $this->ioService->readPort($this->slave->reveal(), 32);
    }

    public function testReadPortsFromEeprom(): void
    {
        $this->eventService->fire('beforeReadPortsFromEeprom', ['slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterReadPortsFromEeprom', ['slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            135,
            'Handtuch',
            1
        );

        $this->ioService->readPortsFromEeprom($this->slave->reveal());
    }

    public function testReadPortsFromEepromNoData(): void
    {
        $this->eventService->fire('beforeReadPortsFromEeprom', ['slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            135,
            'Handtuch',
            1
        );

        $this->expectException(ReceiveError::class);
        $this->ioService->readPortsFromEeprom($this->slave->reveal());
    }

    public function testWritePortsToEeprom(): void
    {
        $this->eventService->fire('beforeWritePortsToEeprom', ['slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWritePortsToEeprom', ['slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        AbstractSlaveTest::prophesizeWrite(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            135,
            'aa'
        );

        $this->ioService->writePortsToEeprom($this->slave->reveal());
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

        AbstractSlaveTest::prophesizeWrite(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            32,
            'Handtuch'
        );
        $this->ioFormatter->getPortAsString(['value' => '1'])
            ->shouldBeCalledOnce()
            ->willReturn('Handtuch')
        ;

        $this->eventService->fire('beforeWritePort', ['slave' => $this->slave->reveal(), 'number' => 32, 'value' => '1']);
        $this->eventService->fire('afterWritePort', ['slave' => $this->slave->reveal(), 'number' => 32, 'value' => '1']);

        $this->slave->getId()
            ->shouldBeCalledOnce()
            ->willReturn(424242)
        ;
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(42)
        ;

        $this->ioService->toggleValue($this->slave->reveal(), 32);
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

        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;
        $this->slave->getId()
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
