<?php declare(strict_types=1);

namespace Gibson\Test\Unit\Service\Slave;

use Codeception\Test\Unit;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\EventService;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;
use GibsonOS\Module\Hc\Service\Slave\AbstractSlave;
use GibsonOS\Module\Hc\Service\TransformService;
use Prophecy\Promise\ReturnPromise;
use Prophecy\Prophecy\ObjectProphecy;

class AbstractHcSlaveTest extends Unit
{
    /**
     * @var AbstractHcSlave
     */
    private $abstractHcSlave;

    /**
     * @var ObjectProphecy|MasterService
     */
    private $masterService;

    /**
     * @var ObjectProphecy|TransformService
     */
    private $transformService;

    /**
     * @var ObjectProphecy|EventService
     */
    private $eventService;

    /**
     * @var ObjectProphecy|ModuleRepository
     */
    private $moduleRepositroy;

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
     * @var AbstractSlaveTest
     */
    private $abstractSlaveTest;

    protected function _before(): void
    {
        $this->masterService = $this->prophesize(MasterService::class);
        $this->transformService = $this->prophesize(TransformService::class);
        $this->eventService = $this->prophesize(EventService::class);
        $this->moduleRepositroy = $this->prophesize(ModuleRepository::class);
        $this->typeRepository = $this->prophesize(TypeRepository::class);
        $this->masterRepository = $this->prophesize(MasterRepository::class);
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->slaveFactory = $this->prophesize(SlaveFactory::class);
        $this->slave = $this->prophesize(Module::class);

        $this->abstractHcSlave = new class($this->masterService->reveal(), $this->transformService->reveal(), $this->eventService->reveal(), $this->moduleRepositroy->reveal(), $this->typeRepository->reveal(), $this->masterRepository->reveal(), $this->logRepository->reveal(), $this->slaveFactory->reveal(), $this->slave->reveal()) extends AbstractHcSlave {
            /**
             * @var Module
             */
            private $slave;

            public function __construct(MasterService $masterService, TransformService $transformService, EventService $eventService, ModuleRepository $moduleRepository, TypeRepository $typeRepository, MasterRepository $masterRepository, LogRepository $logRepository, SlaveFactory $slaveFactory, Module $slave)
            {
                parent::__construct(
                    $masterService,
                    $transformService,
                    $eventService,
                    $moduleRepository,
                    $typeRepository,
                    $masterRepository,
                    $logRepository,
                    $slaveFactory
                );

                $this->slave = $slave;
            }

            public function onOverwriteExistingSlave(Module $slave, Module $existingSlave): Module
            {
                return $this->slave;
            }

            public function receive(Module $slave, int $type, int $command, string $data): void
            {
            }
        };
    }

    /**
     * @dataProvider getReadAllLedsData
     */
    public function testReadAllLeds(int $return, array $excepted): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            229,
            'A'
        );
        $this->transformService->asciiToUnsignedInt('A')
            ->shouldBeCalledOnce()
            ->willReturn($return)
        ;
        $this->eventService->fire('readAllLeds', array_merge($excepted, ['slave' => $this->slave->reveal()]))
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($excepted, $this->abstractHcSlave->readAllLeds($this->slave->reveal()));
    }

    /**
     * @dataProvider getLedStatusData
     */
    public function testReadLedStatus(int $return, array $excepted): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            220,
            'A'
        );
        $this->transformService->asciiToUnsignedInt('A')
            ->shouldBeCalledOnce()
            ->willReturn($return)
        ;
        $this->eventService->fire('readLedStatus', array_merge($excepted, ['slave' => $this->slave->reveal()]))
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($excepted, $this->abstractHcSlave->readLedStatus($this->slave->reveal()));
    }

    /**
     * @dataProvider getReadRgbLedData
     */
    public function testReadRgbLed(string $return, array $excepted): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            228,
            'ABCD12345'
        );
        $this->transformService->asciiToHex('ABCD12345')
            ->shouldBeCalledTimes(2)
            ->will(new ReturnPromise(['Unwarscheinlich', $return]))
        ;
        $this->eventService->fire('readRgbLed', array_merge($excepted, ['slave' => $this->slave->reveal()]))
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($excepted, $this->abstractHcSlave->readRgbLed($this->slave->reveal()));
    }

    public function testReadBufferSize(): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            216,
            'AB'
        );
        $this->transformService->asciiToUnsignedInt('AB')
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->eventService->fire('readBufferSize', ['bufferSize' => 4242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(4242, $this->abstractHcSlave->readBufferSize($this->slave->reveal()));
    }

    public function testReadDeviceId(): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            200,
            'AB'
        );
        $this->transformService->asciiToUnsignedInt('AB')
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->eventService->fire('readDeviceId', ['deviceId' => 4242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(4242, $this->abstractHcSlave->readDeviceId($this->slave->reveal()));
    }

    public function testReadEepromFree(): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            213,
            'DF'
        );
        $this->transformService->asciiToUnsignedInt('DF')
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->eventService->fire('readEepromFree', ['eepromFree' => 4242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(4242, $this->abstractHcSlave->readEepromFree($this->slave->reveal()));
    }

    public function testReadEepromPosition(): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            214,
            'DF'
        );
        $this->transformService->asciiToUnsignedInt('DF')
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->eventService->fire('readEepromPosition', ['eepromPosition' => 4242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(4242, $this->abstractHcSlave->readEepromPosition($this->slave->reveal()));
    }

    public function testReadEepromSize(): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            212,
            'DF'
        );
        $this->transformService->asciiToUnsignedInt('DF')
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->eventService->fire('readEepromSize', ['eepromSize' => 4242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(4242, $this->abstractHcSlave->readEepromSize($this->slave->reveal()));
    }

    public function testReadHertz(): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            211,
            'acdc'
        );
        $this->transformService->asciiToUnsignedInt('acdc')
            ->shouldBeCalledOnce()
            ->willReturn(42424242)
        ;
        $this->eventService->fire('readHertz', ['hertz' => 42424242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(42424242, $this->abstractHcSlave->readHertz($this->slave->reveal()));
    }

    public function testReadPwmSpeed(): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            217,
            'XY'
        );
        $this->transformService->asciiToUnsignedInt('XY')
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->eventService->fire('readPwmSpeed', ['pwmSpeed' => 4242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(4242, $this->abstractHcSlave->readPwmSpeed($this->slave->reveal()));
    }

    public function testReadTypeId(): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            201,
            'Z'
        );
        $this->transformService->asciiToUnsignedInt('Z')
            ->shouldBeCalledOnce()
            ->willReturn(42)
        ;
        $this->eventService->fire('readTypeId', ['typeId' => 42, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(42, $this->abstractHcSlave->readTypeId($this->slave->reveal()));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadPowerLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            221,
            'C'
        );
        $this->transformService->asciiToUnsignedInt('C')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('readPowerLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readPowerLed($this->slave->reveal()));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadErrorLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            222,
            'C'
        );
        $this->transformService->asciiToUnsignedInt('C')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('readErrorLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readErrorLed($this->slave->reveal()));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadConnectLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            223,
            'C'
        );
        $this->transformService->asciiToUnsignedInt('C')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('readConnectLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readConnectLed($this->slave->reveal()));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadTransreceiveLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            224,
            'C'
        );
        $this->transformService->asciiToUnsignedInt('C')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('readTransreceiveLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readTransreceiveLed($this->slave->reveal()));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadTransceiveLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            225,
            'C'
        );
        $this->transformService->asciiToUnsignedInt('C')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('readTransceiveLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readTransceiveLed($this->slave->reveal()));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadReceiveLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            226,
            'C'
        );
        $this->transformService->asciiToUnsignedInt('C')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('readReceiveLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readReceiveLed($this->slave->reveal()));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadCustomLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            227,
            'C'
        );
        $this->transformService->asciiToUnsignedInt('C')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('readCustomLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readCustomLed($this->slave->reveal()));
    }

    public function testWriteAddress(): void
    {
        $master = $this->prophesize(Master::class);
        AbstractSlaveTest::prophesizeWrite(
            $master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            7,
            202,
            chr(4242 >> 8) . chr(4242 & 255) . chr(42)
        );
        $this->slave->getDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->slave->setAddress(42)
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;
        $this->eventService->fire('beforeWriteAddress', ['newAddress' => 42, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteAddress', ['newAddress' => 42, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->slave->getMaster()->shouldBeCalledTimes(4);
        $this->masterService->scanBus($master->reveal());

        $this->abstractHcSlave->writeAddress($this->slave->reveal(), 42);
    }

    public function testWriteDeviceId(): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            200,
            chr(4242 >> 8) . chr(4242 & 255) . chr(7777 >> 8) . chr(7777 & 255)
        );
        $this->slave->getDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->slave->setDeviceId(7777)
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;
        $this->eventService->fire('beforeWriteDeviceId', ['newDeviceId' => 7777, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteDeviceId', ['newDeviceId' => 7777, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeDeviceId($this->slave->reveal(), 7777);
    }

    public function testWriteEepromErase(): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            215,
            chr(4242 >> 8) . chr(4242 & 255)
        );
        $this->slave->getDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->eventService->fire('beforeWriteEepromErase', ['slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteEepromErase', ['slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeEepromErase($this->slave->reveal());
    }

    public function testWriteEepromPosition(): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            214,
            chr(4242 >> 8) . chr(4242 & 255)
        );
        $this->eventService->fire('beforeWriteEepromPosition', ['eepromPosition' => 4242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteEepromPosition', ['eepromPosition' => 4242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeEepromPosition($this->slave->reveal(), 4242);
    }

    public function testWritePwmSpeed(): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            217,
            chr(4242 >> 8) . chr(4242 & 255)
        );
        $this->eventService->fire('beforeWritePwmSpeed', ['pwmSpeed' => 4242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWritePwmSpeed', ['pwmSpeed' => 4242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writePwmSpeed($this->slave->reveal(), 4242);
    }

    public function testWriteRestart(): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            209,
            chr(4242 >> 8) . chr(4242 & 255)
        );
        $this->slave->getDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->eventService->fire('beforeWriteRestart', ['slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteRestart', ['slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeRestart($this->slave->reveal());
    }

    public function testWriteTypeId(): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            201,
            chr(7) . 'a'
        );
        $this->eventService->fire('beforeWriteTypeId', ['typeId' => 7, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteTypeId', ['typeId' => 7, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $type = $this->prophesize(Type::class);
        $type->getId()
            ->shouldBeCalledTimes(3)
            ->willReturn(7)
        ;
        $type->getHelper()
            ->shouldBeCalledOnce()
            ->willReturn('prefect')
        ;
        $this->slave->setType($type->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;
        $slaveService = $this->prophesize(AbstractSlave::class);
        $slaveService->handshake($this->slave->reveal())
            ->shouldBeCalledOnce()
        ;
        $this->slaveFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($slaveService->reveal())
        ;

        $this->abstractHcSlave->writeTypeId($this->slave->reveal(), $type->reveal());
    }

    /**
     * @dataProvider getLedData
     */
    public function testWritePowerLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            221,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('beforeWritePowerLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWritePowerLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writePowerLed($this->slave->reveal(), $on);
    }

    /**
     * @dataProvider getLedData
     */
    public function testWriteErrorLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            222,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('beforeWriteErrorLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteErrorLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeErrorLed($this->slave->reveal(), $on);
    }

    /**
     * @dataProvider getLedData
     */
    public function testWriteConnectLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            223,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('beforeWriteConnectLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteConnectLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeConnectLed($this->slave->reveal(), $on);
    }

    /**
     * @dataProvider getLedData
     */
    public function testWriteTransreceiveLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            224,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('beforeWriteTransreceiveLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteTransreceiveLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeTransreceiveLed($this->slave->reveal(), $on);
    }

    /**
     * @dataProvider getLedData
     */
    public function testWriteTransceiveLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            225,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('beforeWriteTransceiveLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteTransceiveLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeTransceiveLed($this->slave->reveal(), $on);
    }

    /**
     * @dataProvider getLedData
     */
    public function testWriteReceiveLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            226,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('beforeWriteReceiveLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteReceiveLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeReceiveLed($this->slave->reveal(), $on);
    }

    /**
     * @dataProvider getLedData
     */
    public function testWriteCustomLed(bool $on): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            227,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('beforeWriteCustomLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteCustomLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeCustomLed($this->slave->reveal(), $on);
    }

    public function getLedData(): array
    {
        return [
            'On' => [true],
            'Off' => [false],
        ];
    }

    public function getReadAllLedsData(): array
    {
        return [
            [0, $this->getReadAllLedsExcepted([], false, true)],
            [1, $this->getReadAllLedsExcepted([], false, true)],
            [2, $this->getReadAllLedsExcepted(['custom' => true], false, true)],
            [3, $this->getReadAllLedsExcepted(['custom' => true], false, true)],
            [4, $this->getReadAllLedsExcepted(['receive' => true], false, true)],
            [5, $this->getReadAllLedsExcepted(['receive' => true], false, true)],
            [6, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true], false, true)],
            [7, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true], false, true)],
            [8, $this->getReadAllLedsExcepted(['transceive' => true], false, true)],
            [9, $this->getReadAllLedsExcepted(['transceive' => true], false, true)],
            [10, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true], false, true)],
            [11, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true], false, true)],
            [12, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true], false, true)],
            [13, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true], false, true)],
            [14, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true], false, true)],
            [15, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true], false, true)],
            [16, $this->getReadAllLedsExcepted(['transreceive' => true], false, true)],
            [17, $this->getReadAllLedsExcepted(['transreceive' => true], false, true)],
            [18, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true], false, true)],
            [19, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true], false, true)],
            [20, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true], false, true)],
            [21, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true], false, true)],
            [22, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true], false, true)],
            [23, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true], false, true)],
            [24, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true], false, true)],
            [25, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true], false, true)],
            [26, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true], false, true)],
            [27, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true], false, true)],
            [28, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true], false, true)],
            [29, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true], false, true)],
            [30, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true], false, true)],
            [31, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true], false, true)],
            [32, $this->getReadAllLedsExcepted(['connect' => true], false, true)],
            [33, $this->getReadAllLedsExcepted(['connect' => true], false, true)],
            [34, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true], false, true)],
            [35, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true], false, true)],
            [36, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true], false, true)],
            [37, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true], false, true)],
            [38, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true], false, true)],
            [39, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true], false, true)],
            [40, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true], false, true)],
            [41, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true], false, true)],
            [42, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true], false, true)],
            [43, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true], false, true)],
            [44, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true], false, true)],
            [45, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true], false, true)],
            [46, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true], false, true)],
            [47, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true], false, true)],
            [48, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true], false, true)],
            [49, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true], false, true)],
            [50, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [51, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [52, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [53, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [54, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [55, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [56, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [57, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [58, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [59, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [60, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [61, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [62, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [63, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [64, $this->getReadAllLedsExcepted(['error' => true], false, true)],
            [65, $this->getReadAllLedsExcepted(['error' => true], false, true)],
            [66, $this->getReadAllLedsExcepted(['custom' => true, 'error' => true], false, true)],
            [67, $this->getReadAllLedsExcepted(['custom' => true, 'error' => true], false, true)],
            [68, $this->getReadAllLedsExcepted(['receive' => true, 'error' => true], false, true)],
            [69, $this->getReadAllLedsExcepted(['receive' => true, 'error' => true], false, true)],
            [70, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'error' => true], false, true)],
            [71, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'error' => true], false, true)],
            [72, $this->getReadAllLedsExcepted(['transceive' => true, 'error' => true], false, true)],
            [73, $this->getReadAllLedsExcepted(['transceive' => true, 'error' => true], false, true)],
            [74, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'error' => true], false, true)],
            [75, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'error' => true], false, true)],
            [76, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'error' => true], false, true)],
            [77, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'error' => true], false, true)],
            [78, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'error' => true], false, true)],
            [79, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'error' => true], false, true)],
            [80, $this->getReadAllLedsExcepted(['transreceive' => true, 'error' => true], false, true)],
            [81, $this->getReadAllLedsExcepted(['transreceive' => true, 'error' => true], false, true)],
            [82, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'error' => true], false, true)],
            [83, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'error' => true], false, true)],
            [84, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [85, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [86, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [87, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [88, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [89, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [90, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [91, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [92, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [93, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [94, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [95, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [96, $this->getReadAllLedsExcepted(['connect' => true, 'error' => true], false, true)],
            [97, $this->getReadAllLedsExcepted(['connect' => true, 'error' => true], false, true)],
            [98, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'error' => true], false, true)],
            [99, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'error' => true], false, true)],
            [100, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'error' => true], false, true)],
            [101, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'error' => true], false, true)],
            [102, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'error' => true], false, true)],
            [103, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'error' => true], false, true)],
            [104, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'error' => true], false, true)],
            [105, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'error' => true], false, true)],
            [106, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'error' => true], false, true)],
            [107, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'error' => true], false, true)],
            [108, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'error' => true], false, true)],
            [109, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'error' => true], false, true)],
            [110, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true], false, true)],
            [111, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true], false, true)],
            [112, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [113, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [114, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [115, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [116, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [117, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [118, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [119, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [120, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [121, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [122, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [123, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [124, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [125, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [126, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [127, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [128, $this->getReadAllLedsExcepted(['power' => true], false, true)],
            [129, $this->getReadAllLedsExcepted(['power' => true], false, true)],
            [130, $this->getReadAllLedsExcepted(['custom' => true, 'power' => true], false, true)],
            [131, $this->getReadAllLedsExcepted(['custom' => true, 'power' => true], false, true)],
            [132, $this->getReadAllLedsExcepted(['receive' => true, 'power' => true], false, true)],
            [133, $this->getReadAllLedsExcepted(['receive' => true, 'power' => true], false, true)],
            [134, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'power' => true], false, true)],
            [135, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'power' => true], false, true)],
            [136, $this->getReadAllLedsExcepted(['transceive' => true, 'power' => true], false, true)],
            [137, $this->getReadAllLedsExcepted(['transceive' => true, 'power' => true], false, true)],
            [138, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'power' => true], false, true)],
            [139, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'power' => true], false, true)],
            [140, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'power' => true], false, true)],
            [141, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'power' => true], false, true)],
            [142, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'power' => true], false, true)],
            [143, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'power' => true], false, true)],
            [144, $this->getReadAllLedsExcepted(['transreceive' => true, 'power' => true], false, true)],
            [145, $this->getReadAllLedsExcepted(['transreceive' => true, 'power' => true], false, true)],
            [146, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'power' => true], false, true)],
            [147, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'power' => true], false, true)],
            [148, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [149, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [150, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [151, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [152, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [153, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [154, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [155, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [156, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [157, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [158, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [159, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [160, $this->getReadAllLedsExcepted(['connect' => true, 'power' => true], false, true)],
            [161, $this->getReadAllLedsExcepted(['connect' => true, 'power' => true], false, true)],
            [162, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'power' => true], false, true)],
            [163, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'power' => true], false, true)],
            [164, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'power' => true], false, true)],
            [165, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'power' => true], false, true)],
            [166, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'power' => true], false, true)],
            [167, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'power' => true], false, true)],
            [168, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'power' => true], false, true)],
            [169, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'power' => true], false, true)],
            [170, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'power' => true], false, true)],
            [171, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'power' => true], false, true)],
            [172, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'power' => true], false, true)],
            [173, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'power' => true], false, true)],
            [174, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'power' => true], false, true)],
            [175, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'power' => true], false, true)],
            [176, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [177, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [178, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [179, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [180, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [181, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [182, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [183, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [184, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [185, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [186, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [187, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [188, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [189, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [190, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [191, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [192, $this->getReadAllLedsExcepted(['error' => true, 'power' => true], false, true)],
            [193, $this->getReadAllLedsExcepted(['error' => true, 'power' => true], false, true)],
            [194, $this->getReadAllLedsExcepted(['custom' => true, 'error' => true, 'power' => true], false, true)],
            [195, $this->getReadAllLedsExcepted(['custom' => true, 'error' => true, 'power' => true], false, true)],
            [196, $this->getReadAllLedsExcepted(['receive' => true, 'error' => true, 'power' => true], false, true)],
            [197, $this->getReadAllLedsExcepted(['receive' => true, 'error' => true, 'power' => true], false, true)],
            [198, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'error' => true, 'power' => true], false, true)],
            [199, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'error' => true, 'power' => true], false, true)],
            [200, $this->getReadAllLedsExcepted(['transceive' => true, 'error' => true, 'power' => true], false, true)],
            [201, $this->getReadAllLedsExcepted(['transceive' => true, 'error' => true, 'power' => true], false, true)],
            [202, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'error' => true, 'power' => true], false, true)],
            [203, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'error' => true, 'power' => true], false, true)],
            [204, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'error' => true, 'power' => true], false, true)],
            [205, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'error' => true, 'power' => true], false, true)],
            [206, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'error' => true, 'power' => true], false, true)],
            [207, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'error' => true, 'power' => true], false, true)],
            [208, $this->getReadAllLedsExcepted(['transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [209, $this->getReadAllLedsExcepted(['transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [210, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [211, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [212, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [213, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [214, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [215, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [216, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [217, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [218, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [219, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [220, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [221, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [222, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [223, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [224, $this->getReadAllLedsExcepted(['connect' => true, 'error' => true, 'power' => true], false, true)],
            [225, $this->getReadAllLedsExcepted(['connect' => true, 'error' => true, 'power' => true], false, true)],
            [226, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [227, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [228, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [229, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [230, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [231, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [232, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [233, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [234, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [235, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [236, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [237, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [238, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [239, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [240, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [241, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [242, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [243, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [244, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [245, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [246, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [247, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [248, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [249, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [250, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [251, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [252, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [253, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [254, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [255, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
        ];
    }

    public function getLedStatusData(): array
    {
        return [
            [0, $this->getReadAllLedsExcepted([], true, true)],
            [1, $this->getReadAllLedsExcepted(['rgb' => true], true, true)],
            [2, $this->getReadAllLedsExcepted(['custom' => true], true, true)],
            [3, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true], true, true)],
            [4, $this->getReadAllLedsExcepted(['receive' => true], true, true)],
            [5, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true], true, true)],
            [6, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true], true, true)],
            [7, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true], true, true)],
            [8, $this->getReadAllLedsExcepted(['transceive' => true], true, true)],
            [9, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true], true, true)],
            [10, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true], true, true)],
            [11, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true], true, true)],
            [12, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true], true, true)],
            [13, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true], true, true)],
            [14, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true], true, true)],
            [15, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true], true, true)],
            [16, $this->getReadAllLedsExcepted(['transreceive' => true], true, true)],
            [17, $this->getReadAllLedsExcepted(['rgb' => true, 'transreceive' => true], true, true)],
            [18, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true], true, true)],
            [19, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transreceive' => true], true, true)],
            [20, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true], true, true)],
            [21, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transreceive' => true], true, true)],
            [22, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true], true, true)],
            [23, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transreceive' => true], true, true)],
            [24, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true], true, true)],
            [25, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'transreceive' => true], true, true)],
            [26, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true], true, true)],
            [27, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'transreceive' => true], true, true)],
            [28, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true], true, true)],
            [29, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true], true, true)],
            [30, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true], true, true)],
            [31, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true], true, true)],
            [32, $this->getReadAllLedsExcepted(['connect' => true], true, true)],
            [33, $this->getReadAllLedsExcepted(['rgb' => true, 'connect' => true], true, true)],
            [34, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true], true, true)],
            [35, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'connect' => true], true, true)],
            [36, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true], true, true)],
            [37, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'connect' => true], true, true)],
            [38, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true], true, true)],
            [39, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'connect' => true], true, true)],
            [40, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true], true, true)],
            [41, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'connect' => true], true, true)],
            [42, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true], true, true)],
            [43, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'connect' => true], true, true)],
            [44, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true], true, true)],
            [45, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'connect' => true], true, true)],
            [46, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true], true, true)],
            [47, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true], true, true)],
            [48, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true], true, true)],
            [49, $this->getReadAllLedsExcepted(['rgb' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [50, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [51, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [52, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [53, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [54, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [55, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [56, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [57, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [58, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [59, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [60, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [61, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [62, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [63, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], true, true)],
            [64, $this->getReadAllLedsExcepted(['error' => true], true, true)],
            [65, $this->getReadAllLedsExcepted(['rgb' => true, 'error' => true], true, true)],
            [66, $this->getReadAllLedsExcepted(['custom' => true, 'error' => true], true, true)],
            [67, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'error' => true], true, true)],
            [68, $this->getReadAllLedsExcepted(['receive' => true, 'error' => true], true, true)],
            [69, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'error' => true], true, true)],
            [70, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'error' => true], true, true)],
            [71, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'error' => true], true, true)],
            [72, $this->getReadAllLedsExcepted(['transceive' => true, 'error' => true], true, true)],
            [73, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'error' => true], true, true)],
            [74, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'error' => true], true, true)],
            [75, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'error' => true], true, true)],
            [76, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'error' => true], true, true)],
            [77, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'error' => true], true, true)],
            [78, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'error' => true], true, true)],
            [79, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'error' => true], true, true)],
            [80, $this->getReadAllLedsExcepted(['transreceive' => true, 'error' => true], true, true)],
            [81, $this->getReadAllLedsExcepted(['rgb' => true, 'transreceive' => true, 'error' => true], true, true)],
            [82, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'error' => true], true, true)],
            [83, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transreceive' => true, 'error' => true], true, true)],
            [84, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'error' => true], true, true)],
            [85, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transreceive' => true, 'error' => true], true, true)],
            [86, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true], true, true)],
            [87, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true], true, true)],
            [88, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'error' => true], true, true)],
            [89, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], true, true)],
            [90, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], true, true)],
            [91, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], true, true)],
            [92, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], true, true)],
            [93, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], true, true)],
            [94, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], true, true)],
            [95, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], true, true)],
            [96, $this->getReadAllLedsExcepted(['connect' => true, 'error' => true], true, true)],
            [97, $this->getReadAllLedsExcepted(['rgb' => true, 'connect' => true, 'error' => true], true, true)],
            [98, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'error' => true], true, true)],
            [99, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'connect' => true, 'error' => true], true, true)],
            [100, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'error' => true], true, true)],
            [101, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'connect' => true, 'error' => true], true, true)],
            [102, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'error' => true], true, true)],
            [103, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'connect' => true, 'error' => true], true, true)],
            [104, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'error' => true], true, true)],
            [105, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'connect' => true, 'error' => true], true, true)],
            [106, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'error' => true], true, true)],
            [107, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'connect' => true, 'error' => true], true, true)],
            [108, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'error' => true], true, true)],
            [109, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true], true, true)],
            [110, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true], true, true)],
            [111, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true], true, true)],
            [112, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [113, $this->getReadAllLedsExcepted(['rgb' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [114, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [115, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [116, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [117, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [118, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [119, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [120, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [121, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [122, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [123, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [124, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [125, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [126, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [127, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], true, true)],
            [128, $this->getReadAllLedsExcepted(['power' => true], true, true)],
            [129, $this->getReadAllLedsExcepted(['rgb' => true, 'power' => true], true, true)],
            [130, $this->getReadAllLedsExcepted(['custom' => true, 'power' => true], true, true)],
            [131, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'power' => true], true, true)],
            [132, $this->getReadAllLedsExcepted(['receive' => true, 'power' => true], true, true)],
            [133, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'power' => true], true, true)],
            [134, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'power' => true], true, true)],
            [135, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'power' => true], true, true)],
            [136, $this->getReadAllLedsExcepted(['transceive' => true, 'power' => true], true, true)],
            [137, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'power' => true], true, true)],
            [138, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'power' => true], true, true)],
            [139, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'power' => true], true, true)],
            [140, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'power' => true], true, true)],
            [141, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'power' => true], true, true)],
            [142, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'power' => true], true, true)],
            [143, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'power' => true], true, true)],
            [144, $this->getReadAllLedsExcepted(['transreceive' => true, 'power' => true], true, true)],
            [145, $this->getReadAllLedsExcepted(['rgb' => true, 'transreceive' => true, 'power' => true], true, true)],
            [146, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'power' => true], true, true)],
            [147, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transreceive' => true, 'power' => true], true, true)],
            [148, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'power' => true], true, true)],
            [149, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transreceive' => true, 'power' => true], true, true)],
            [150, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'power' => true], true, true)],
            [151, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transreceive' => true, 'power' => true], true, true)],
            [152, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'power' => true], true, true)],
            [153, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], true, true)],
            [154, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], true, true)],
            [155, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], true, true)],
            [156, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], true, true)],
            [157, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], true, true)],
            [158, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], true, true)],
            [159, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], true, true)],
            [160, $this->getReadAllLedsExcepted(['connect' => true, 'power' => true], true, true)],
            [161, $this->getReadAllLedsExcepted(['rgb' => true, 'connect' => true, 'power' => true], true, true)],
            [162, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'power' => true], true, true)],
            [163, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'connect' => true, 'power' => true], true, true)],
            [164, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'power' => true], true, true)],
            [165, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'connect' => true, 'power' => true], true, true)],
            [166, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'power' => true], true, true)],
            [167, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'connect' => true, 'power' => true], true, true)],
            [168, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'power' => true], true, true)],
            [169, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'connect' => true, 'power' => true], true, true)],
            [170, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'power' => true], true, true)],
            [171, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'connect' => true, 'power' => true], true, true)],
            [172, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'power' => true], true, true)],
            [173, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'power' => true], true, true)],
            [174, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'power' => true], true, true)],
            [175, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'power' => true], true, true)],
            [176, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [177, $this->getReadAllLedsExcepted(['rgb' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [178, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [179, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [180, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [181, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [182, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [183, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [184, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [185, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [186, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [187, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [188, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [189, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [190, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [191, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], true, true)],
            [192, $this->getReadAllLedsExcepted(['error' => true, 'power' => true], true, true)],
            [193, $this->getReadAllLedsExcepted(['rgb' => true, 'error' => true, 'power' => true], true, true)],
            [194, $this->getReadAllLedsExcepted(['custom' => true, 'error' => true, 'power' => true], true, true)],
            [195, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'error' => true, 'power' => true], true, true)],
            [196, $this->getReadAllLedsExcepted(['receive' => true, 'error' => true, 'power' => true], true, true)],
            [197, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'error' => true, 'power' => true], true, true)],
            [198, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'error' => true, 'power' => true], true, true)],
            [199, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'error' => true, 'power' => true], true, true)],
            [200, $this->getReadAllLedsExcepted(['transceive' => true, 'error' => true, 'power' => true], true, true)],
            [201, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'error' => true, 'power' => true], true, true)],
            [202, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'error' => true, 'power' => true], true, true)],
            [203, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'error' => true, 'power' => true], true, true)],
            [204, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'error' => true, 'power' => true], true, true)],
            [205, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'error' => true, 'power' => true], true, true)],
            [206, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'error' => true, 'power' => true], true, true)],
            [207, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'error' => true, 'power' => true], true, true)],
            [208, $this->getReadAllLedsExcepted(['transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [209, $this->getReadAllLedsExcepted(['rgb' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [210, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [211, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [212, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [213, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [214, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [215, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [216, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [217, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [218, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [219, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [220, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [221, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [222, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [223, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], true, true)],
            [224, $this->getReadAllLedsExcepted(['connect' => true, 'error' => true, 'power' => true], true, true)],
            [225, $this->getReadAllLedsExcepted(['rgb' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [226, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [227, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [228, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [229, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [230, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [231, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [232, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [233, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [234, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [235, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [236, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [237, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [238, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [239, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [240, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [241, $this->getReadAllLedsExcepted(['rgb' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [242, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [243, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [244, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [245, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [246, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [247, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [248, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [249, $this->getReadAllLedsExcepted(['rgb' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [250, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [251, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [252, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [253, $this->getReadAllLedsExcepted(['rgb' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [254, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
            [255, $this->getReadAllLedsExcepted(['rgb' => true, 'custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], true, true)],
        ];
    }

    public function getReadRgbLedData(): array
    {
        return [
            [
                '000000000000000000',
                [
                    'power' => '000',
                    'error' => '000',
                    'connect' => '000',
                    'transceive' => '000',
                    'receive' => '000',
                    'custom' => '000',
                ],
            ],
            [
                '0123456789ABCDEF01',
                [
                    'power' => '012',
                    'error' => '345',
                    'connect' => '678',
                    'transceive' => '9AB',
                    'receive' => 'CDE',
                    'custom' => 'F01',
                ],
            ],
            [
                '000FFF000FFF000FFF',
                [
                    'power' => '000',
                    'error' => 'FFF',
                    'connect' => '000',
                    'transceive' => 'FFF',
                    'receive' => '000',
                    'custom' => 'FFF',
                ],
            ],
        ];
    }

    private function getReadAllLedsExcepted(array $trues = [], $withRgb = false, $withTransreceive = false): array
    {
        $leds = [
            'power' => false,
            'error' => false,
            'connect' => false,
            'transceive' => false,
            'receive' => false,
            'custom' => false,
        ];

        if ($withTransreceive) {
            $leds['transreceive'] = false;
        }

        if ($withRgb) {
            $leds['rgb'] = false;
        }

        return array_merge($leds, $trues);
    }
}
