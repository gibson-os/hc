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
            [0, $this->getReadAllLedsExcepted()],
            [1, $this->getReadAllLedsExcepted()],
            [2, $this->getReadAllLedsExcepted(['custom' => true])],
            [3, $this->getReadAllLedsExcepted(['custom' => true])],
            [4, $this->getReadAllLedsExcepted(['receive' => true])],
            [5, $this->getReadAllLedsExcepted(['receive' => true])],
            [6, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true])],
            [7, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true])],
            [8, $this->getReadAllLedsExcepted(['transceive' => true])],
            [9, $this->getReadAllLedsExcepted(['transceive' => true])],
            [10, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true])],
            [11, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true])],
            [12, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true])],
            [13, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true])],
            [14, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true])],
            [15, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true])],
            [16, $this->getReadAllLedsExcepted(['transreceive' => true])],
            [17, $this->getReadAllLedsExcepted(['transreceive' => true])],
            [18, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true])],
            [19, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true])],
            [20, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true])],
            [21, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true])],
            [22, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true])],
            [23, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true])],
            [24, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true])],
            [25, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true])],
            [26, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true])],
            [27, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true])],
            [28, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true])],
            [29, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true])],
            [30, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true])],
            [31, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true])],
            [32, $this->getReadAllLedsExcepted(['connect' => true])],
            [33, $this->getReadAllLedsExcepted(['connect' => true])],
            [34, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true])],
            [35, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true])],
            [36, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true])],
            [37, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true])],
            [38, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true])],
            [39, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true])],
            [40, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true])],
            [41, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true])],
            [42, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true])],
            [43, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true])],
            [44, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true])],
            [45, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true])],
            [46, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true])],
            [47, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true])],
            [48, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true])],
            [49, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true])],
            [50, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true])],
            [51, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true])],
            [52, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true])],
            [53, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true])],
            [54, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true])],
            [55, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true])],
            [56, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true])],
            [57, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true])],
            [58, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true])],
            [59, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true])],
            [60, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true])],
            [61, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true])],
            [62, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true])],
            [63, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true])],
            [64, $this->getReadAllLedsExcepted(['error' => true])],
            [65, $this->getReadAllLedsExcepted(['error' => true])],
            [66, $this->getReadAllLedsExcepted(['custom' => true, 'error' => true])],
            [67, $this->getReadAllLedsExcepted(['custom' => true, 'error' => true])],
            [68, $this->getReadAllLedsExcepted(['receive' => true, 'error' => true])],
            [69, $this->getReadAllLedsExcepted(['receive' => true, 'error' => true])],
            [70, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'error' => true])],
            [71, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'error' => true])],
            [72, $this->getReadAllLedsExcepted(['transceive' => true, 'error' => true])],
            [73, $this->getReadAllLedsExcepted(['transceive' => true, 'error' => true])],
            [74, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'error' => true])],
            [75, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'error' => true])],
            [76, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'error' => true])],
            [77, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'error' => true])],
            [78, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'error' => true])],
            [79, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'error' => true])],
            [80, $this->getReadAllLedsExcepted(['transreceive' => true, 'error' => true])],
            [81, $this->getReadAllLedsExcepted(['transreceive' => true, 'error' => true])],
            [82, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'error' => true])],
            [83, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'error' => true])],
            [84, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'error' => true])],
            [85, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'error' => true])],
            [86, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true])],
            [87, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true])],
            [88, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'error' => true])],
            [89, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'error' => true])],
            [90, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true])],
            [91, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true])],
            [92, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true])],
            [93, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true])],
            [94, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true])],
            [95, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true])],
            [96, $this->getReadAllLedsExcepted(['connect' => true, 'error' => true])],
            [97, $this->getReadAllLedsExcepted(['connect' => true, 'error' => true])],
            [98, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'error' => true])],
            [99, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'error' => true])],
            [100, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'error' => true])],
            [101, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'error' => true])],
            [102, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'error' => true])],
            [103, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'error' => true])],
            [104, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'error' => true])],
            [105, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'error' => true])],
            [106, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'error' => true])],
            [107, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'error' => true])],
            [108, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'error' => true])],
            [109, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'error' => true])],
            [110, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true])],
            [111, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true])],
            [112, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'error' => true])],
            [113, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'error' => true])],
            [114, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [115, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [116, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [117, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [118, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [119, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [120, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [121, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [122, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [123, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [124, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [125, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [126, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [127, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true])],
            [128, $this->getReadAllLedsExcepted(['power' => true])],
            [129, $this->getReadAllLedsExcepted(['power' => true])],
            [130, $this->getReadAllLedsExcepted(['custom' => true, 'power' => true])],
            [131, $this->getReadAllLedsExcepted(['custom' => true, 'power' => true])],
            [132, $this->getReadAllLedsExcepted(['receive' => true, 'power' => true])],
            [133, $this->getReadAllLedsExcepted(['receive' => true, 'power' => true])],
            [134, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'power' => true])],
            [135, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'power' => true])],
            [136, $this->getReadAllLedsExcepted(['transceive' => true, 'power' => true])],
            [137, $this->getReadAllLedsExcepted(['transceive' => true, 'power' => true])],
            [138, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'power' => true])],
            [139, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'power' => true])],
            [140, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'power' => true])],
            [141, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'power' => true])],
            [142, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'power' => true])],
            [143, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'power' => true])],
            [144, $this->getReadAllLedsExcepted(['transreceive' => true, 'power' => true])],
            [145, $this->getReadAllLedsExcepted(['transreceive' => true, 'power' => true])],
            [146, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'power' => true])],
            [147, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'power' => true])],
            [148, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'power' => true])],
            [149, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'power' => true])],
            [150, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'power' => true])],
            [151, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'power' => true])],
            [152, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'power' => true])],
            [153, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'power' => true])],
            [154, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'power' => true])],
            [155, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'power' => true])],
            [156, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true])],
            [157, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true])],
            [158, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true])],
            [159, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true])],
            [160, $this->getReadAllLedsExcepted(['connect' => true, 'power' => true])],
            [161, $this->getReadAllLedsExcepted(['connect' => true, 'power' => true])],
            [162, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'power' => true])],
            [163, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'power' => true])],
            [164, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'power' => true])],
            [165, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'power' => true])],
            [166, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'power' => true])],
            [167, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'power' => true])],
            [168, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'power' => true])],
            [169, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'power' => true])],
            [170, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'power' => true])],
            [171, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'power' => true])],
            [172, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'power' => true])],
            [173, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'power' => true])],
            [174, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'power' => true])],
            [175, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'power' => true])],
            [176, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'power' => true])],
            [177, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'power' => true])],
            [178, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [179, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [180, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [181, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [182, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [183, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [184, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [185, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [186, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [187, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [188, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [189, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [190, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [191, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true])],
            [192, $this->getReadAllLedsExcepted(['error' => true, 'power' => true])],
            [193, $this->getReadAllLedsExcepted(['error' => true, 'power' => true])],
            [194, $this->getReadAllLedsExcepted(['custom' => true, 'error' => true, 'power' => true])],
            [195, $this->getReadAllLedsExcepted(['custom' => true, 'error' => true, 'power' => true])],
            [196, $this->getReadAllLedsExcepted(['receive' => true, 'error' => true, 'power' => true])],
            [197, $this->getReadAllLedsExcepted(['receive' => true, 'error' => true, 'power' => true])],
            [198, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'error' => true, 'power' => true])],
            [199, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'error' => true, 'power' => true])],
            [200, $this->getReadAllLedsExcepted(['transceive' => true, 'error' => true, 'power' => true])],
            [201, $this->getReadAllLedsExcepted(['transceive' => true, 'error' => true, 'power' => true])],
            [202, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'error' => true, 'power' => true])],
            [203, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'error' => true, 'power' => true])],
            [204, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'error' => true, 'power' => true])],
            [205, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'error' => true, 'power' => true])],
            [206, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'error' => true, 'power' => true])],
            [207, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'error' => true, 'power' => true])],
            [208, $this->getReadAllLedsExcepted(['transreceive' => true, 'error' => true, 'power' => true])],
            [209, $this->getReadAllLedsExcepted(['transreceive' => true, 'error' => true, 'power' => true])],
            [210, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [211, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [212, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [213, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [214, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [215, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [216, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [217, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [218, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [219, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [220, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [221, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [222, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [223, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true])],
            [224, $this->getReadAllLedsExcepted(['connect' => true, 'error' => true, 'power' => true])],
            [225, $this->getReadAllLedsExcepted(['connect' => true, 'error' => true, 'power' => true])],
            [226, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [227, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [228, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [229, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [230, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [231, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [232, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [233, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [234, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [235, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [236, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [237, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [238, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [239, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [240, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [241, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [242, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [243, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [244, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [245, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [246, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [247, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [248, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [249, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [250, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [251, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [252, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [253, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [254, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
            [255, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true])],
        ];
    }

    private function getReadAllLedsExcepted(array $trues = [], $withRgb = false): array
    {
        $leds = [
            'power' => false,
            'error' => false,
            'connect' => false,
            'transreceive' => false,
            'transceive' => false,
            'receive' => false,
            'custom' => false,
        ];

        if ($withRgb) {
            $leds['rgb'] = false;
        }

        return array_merge($leds, $trues);
    }
}
