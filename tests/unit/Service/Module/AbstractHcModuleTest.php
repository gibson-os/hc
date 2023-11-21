<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Service\Module;

use Codeception\Test\Unit;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Module\AbstractHcModule;
use GibsonOS\Module\Hc\Service\Module\AbstractModule;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\Test\Unit\Core\ModelManagerTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class AbstractHcModuleTest extends Unit
{
    use ModelManagerTrait;

    private AbstractHcModule $abstractHcSlave;

    private ObjectProphecy|MasterService $masterService;

    private TransformService|ObjectProphecy $transformService;

    private ObjectProphecy|EventService $eventService;

    private ObjectProphecy|ModuleRepository $moduleRepository;

    private ObjectProphecy|TypeRepository $typeRepository;

    private ObjectProphecy|MasterRepository $masterRepository;

    private ObjectProphecy|ModuleFactory $moduleFactory;

    private ObjectProphecy|LogRepository $logRepository;

    private LoggerInterface|ObjectProphecy $logger;

    private Module $module;

    private Master $master;

    private Type $type;

    protected function _before(): void
    {
        $this->loadModelManager();

        $this->moduleRepository = $this->prophesize(ModuleRepository::class);
        $this->typeRepository = $this->prophesize(TypeRepository::class);
        $this->masterRepository = $this->prophesize(MasterRepository::class);
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->masterService = $this->prophesize(MasterService::class);
        $this->transformService = $this->prophesize(TransformService::class);
        $this->moduleFactory = $this->prophesize(ModuleFactory::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->eventService = $this->prophesize(EventService::class);
        $this->master = (new Master($this->modelWrapper->reveal()))
            ->setId(1)
            ->setAddress('42.42.42.42')
            ->setSendPort(420042)
        ;
        $this->type = (new Type($this->modelWrapper->reveal()))
            ->setId(7)
            ->setHelper('prefect')
        ;
        $this->module = (new Module($this->modelWrapper->reveal()))
            ->setAddress(42)
            ->setDeviceId(4242)
            ->setId(7)
            ->setType($this->type)
            ->setMaster($this->master)
        ;

        $this->abstractHcSlave = new class($this->masterService->reveal(), $this->transformService->reveal(), $this->eventService->reveal(), $this->moduleRepository->reveal(), $this->typeRepository->reveal(), $this->masterRepository->reveal(), $this->logRepository->reveal(), $this->moduleFactory->reveal(), $this->logger->reveal(), $this->modelManager->reveal(), $this->modelWrapper->reveal(), $this->module) extends AbstractHcModule {
            private Module $module;

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
                Module $slave
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

                $this->module = $slave;
            }

            public function slaveHandshake(Module $module): Module
            {
                return $module;
            }

            public function onOverwriteExistingSlave(Module $module, Module $existingSlave): Module
            {
                return $module;
            }

            public function receive(Module $module, BusMessage $busMessage): void
            {
            }

            protected function getEventClassName(): string
            {
                return '';
            }
        };
    }

    public function testHandshakeExistingDevice(): void
    {
        $this->prophesizeReadTypeId($this->module, 7);
        $this->prophesizeReadDeviceId($this->module, 4242);
        $this->prophesizeHandshake($this->module);

        //        $busMessage = (new BusMessage('42.42.42.42', 255))
        //            ->setSlaveAddress(4242)
        //            ->setCommand(201)
        //            ->setPort(420042)
        //            ->setData(chr(42))
        //        ;
        //        $this->masterService->send($this->module->getMaster(), $busMessage)
        //            ->shouldBeCalledOnce()
        //        ;
        //        $this->masterService->receiveReceiveReturn($this->module->getMaster(), $busMessage)
        //            ->shouldBeCalledOnce()
        //        ;

        $this->abstractHcSlave->handshake($this->module);
    }

    public function testHandshakeWrongType(): void
    {
        $this->prophesizeReadTypeId($this->module, 42);
        //        $this->modelWrapper->getTableManager()
        //            ->shouldBeCalledOnce()
        //            ->willReturn()
        //        ;
        $this->typeRepository->getById(42)
            ->shouldBeCalledOnce()
            ->willReturn((new Type($this->modelWrapper->reveal()))->setId(42)->setHelper('prefect'))
        ;
        $slaveService = $this->prophesize(AbstractModule::class);
        $slaveService->handshake($this->module)
            ->shouldBeCalledOnce()
            ->willReturn($this->module)
        ;
        $this->moduleFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($slaveService->reveal())
        ;

        $this->abstractHcSlave->handshake($this->module);
    }

    public function testHandshakeWrongDeviceId(): void
    {
        $this->prophesizeReadTypeId($this->module, 7);
        $this->prophesizeReadDeviceId($this->module, 4200);
        $this->module->setDeviceId(4242);

        $newModule = (new Module($this->modelWrapper->reveal()))
            ->setDeviceId(4242)
        ;
        $this->prophesizeHandshake($newModule);
        $this->moduleRepository->getByDeviceId(4200)
            ->shouldBeCalledOnce()
            ->willReturn($newModule)
        ;

        $this->abstractHcSlave->handshake($this->module);
    }

    public function testHandshakeEmptyId(): void
    {
        $newModule = (new Module($this->modelWrapper->reveal()))
            ->setDeviceId(4242)
            ->setMaster($this->master)
            ->setType($this->type)
        ;
        $this->prophesizeReadTypeId($newModule, 7);
        $this->prophesizeReadDeviceId($newModule, 4242);
        $this->prophesizeHandshake($newModule);
        $this->moduleRepository->getByDeviceId(4242)
            ->shouldBeCalledOnce()
            ->willReturn($newModule)
        ;

        //        $this->masterService->receiveReceiveReturn($this->master)
        //            ->shouldBeCalledOnce()
        //        ;

        $this->abstractHcSlave->handshake($newModule);
    }

    /**
     * @dataProvider getHandshakeIllegalDeviceIdData
     */
    public function testHandshakeIllegalDeviceId(int $deviceId): void
    {
        $this->prophesizeReadTypeId($this->module, 7);
        $this->prophesizeReadDeviceId($this->module, $deviceId);
        $this->prophesizeHandshake($this->module);
        $this->prophesizeWriteDeviceId($this->module, $deviceId, 4242);

        $this->moduleRepository->getByDeviceId($deviceId)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;
        $this->moduleRepository->getFreeDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->typeRepository->getByDefaultAddress(42)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;

        $this->abstractHcSlave->handshake($this->module);
    }

    /**
     * @dataProvider getHandshakeIllegalDeviceIdData
     */
    public function testHandshakeIllegalDeviceIdWrongDeviceIdModule(int $deviceId): void
    {
        $this->module->setDeviceId($deviceId);
        $this->prophesizeReadTypeId($this->module, 7);
        $this->prophesizeReadDeviceId($this->module, 42420);
        $this->prophesizeHandshake($this->module);
        $this->prophesizeWriteDeviceId($this->module, $deviceId, 4242);

        $this->moduleRepository->getFreeDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->moduleRepository->getByDeviceId(42420)
            ->shouldBeCalledOnce()
            ->willReturn($this->module)
        ;

        $this->abstractHcSlave->handshake($this->module);
    }

    public function testHandshakeWrongDeviceIdNewSlave(): void
    {
        $this->prophesizeReadTypeId($this->module, 7);
        $this->prophesizeReadDeviceId($this->module, 4200);
        $this->prophesizeHandshake($this->module);

        $this->moduleRepository->getByDeviceId(4200)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;
        $this->typeRepository->getByDefaultAddress(42)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;

        $this->abstractHcSlave->handshake($this->module);
    }

    public function testHandshakeEmptyIdNewSlave(): void
    {
        $this->prophesizeReadTypeId($this->module, 7);
        $this->prophesizeReadDeviceId($this->module, 4242);
        $this->prophesizeHandshake($this->module);

        $this->abstractHcSlave->handshake($this->module);
    }

    /**
     * @dataProvider getHandshakeIllegalDeviceIdData
     */
    public function testHandshakeWrongAndIllegalDeviceIdNewSlave(int $deviceId): void
    {
        $this->module->setDeviceId($deviceId);
        $this->prophesizeReadTypeId($this->module, 7);
        $this->prophesizeReadDeviceId($this->module, 4242);
        $this->prophesizeHandshake($this->module);

        $this->moduleRepository->getByDeviceId(4242)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;
        $this->typeRepository->getByDefaultAddress(42)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;

        $this->abstractHcSlave->handshake($this->module);
    }

    private function prophesizeHandshake(Module $module): void
    {
        $this->prophesizeReadHertz($module, 420000);
        $this->prophesizeReadBufferSize($module, 4);
        $this->prophesizeReadEepromSize($module, 420042);
        $this->prophesizeReadPwmSpeed($module, 42042042);
    }

    /**
     * @dataProvider getHandshakeData
     */
    public function testHandshake(?int $id, bool $deviceIdEqual, bool $exists, int $deviceId): void
    {
        $this->prophesizeReadTypeId($this->module, 7);

        $this->module->setDeviceId($deviceIdEqual ? $deviceId : $deviceId + 1);

        $this->prophesizeReadDeviceId($this->module, $deviceId);
        $this->prophesizeReadHertz($this->module, 420000);
        $this->prophesizeReadBufferSize($this->module, 4);
        $this->prophesizeReadEepromSize($this->module, 420042);
        $this->prophesizeReadPwmSpeed($this->module, 42042042);

        if (!$deviceIdEqual || $id === null) {
            $getByDeviceIdCall = $this->moduleRepository->getByDeviceId($deviceId)
//                    ->shouldBeCalledOnce()
            ;

            if ($exists) {
                $getByDeviceIdCall->willReturn($this->module);
            } else {
                $getByDeviceIdCall->willThrow(SelectError::class);
                $this->prophesizeWriteAddress($deviceIdEqual ? $deviceId : $deviceId + 1, 42, 24);
                $this->typeRepository->getByDefaultAddress(42)
//                    ->shouldBeCalledOnce()
                    ->willReturn($this->type)
                ;
                $this->masterRepository->getNextFreeAddress(1)
//                    ->shouldBeCalledOnce()
                    ->willReturn(24)
                ;
            }
        }

        if ($deviceId === 0 || $deviceId > 65534) {
            $this->moduleRepository->getFreeDeviceId()
//                ->shouldBeCalledOnce()
                ->willReturn($deviceId)
            ;
            $this->prophesizeWriteDeviceId($this->module, $deviceId, $deviceId);
        }

        $this->abstractHcSlave->handshake($this->module);
    }

    /**
     * @dataProvider getHandshakeDataDifferentType
     */
    public function testHandshakeDifferentTypes(?int $id, bool $deviceIdEqual, int $deviceId): void
    {
        $this->prophesizeReadTypeId($this->module, 7);

        $this->module->setDeviceId($deviceIdEqual ? $deviceId : $deviceId + 1);

        $this->module->setTypeId(9);
        $this->typeRepository->getById(7)
            ->shouldBeCalledOnce()
            ->willReturn($this->type)
        ;
        $moduleService = $this->prophesize(AbstractModule::class);
        $moduleService->handshake($this->module)
            ->shouldBeCalledOnce()
            ->willReturn($this->module)
        ;
        $this->moduleFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($moduleService->reveal())
        ;

        $this->abstractHcSlave->handshake($this->module);
    }

    /**
     * @dataProvider getReadAllLedsData
     */
    public function testReadAllLeds(int $return, array $excepted): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            229,
            'allLeds',
            1
        );
        $this->eventService->fire('', 'readAllLeds', array_merge($excepted, ['slave' => $this->module]))
            ->shouldBeCalledOnce()
        ;
        $this->transformService->asciiToUnsignedInt('allLeds')
            ->shouldBeCalledOnce()
            ->willReturn($return)
        ;
        $this->assertEquals($excepted, $this->abstractHcSlave->readAllLeds($this->module));
    }

    /**
     * @dataProvider getLedStatusData
     */
    public function testReadLedStatus(int $return, array $excepted): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            220,
            'ledStatus',
            1
        );
        $this->eventService->fire('', 'readLedStatus', array_merge($excepted, ['slave' => $this->module]))
            ->shouldBeCalledOnce()
        ;
        $this->transformService->asciiToUnsignedInt('ledStatus')
            ->shouldBeCalledOnce()
            ->willReturn($return)
        ;

        $this->assertEquals($excepted, $this->abstractHcSlave->readLedStatus($this->module));
    }

    /**
     * @dataProvider getReadRgbLedData
     */
    public function testReadRgbLed(string $return, array $excepted): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            228,
            'rgbLed',
            9
        );
        $this->eventService->fire('', 'readRgbLed', array_merge($excepted, ['slave' => $this->module]))
            ->shouldBeCalledOnce()
        ;
        $this->transformService->asciiToHex('rgbLed')
            ->shouldBeCalledOnce()
            ->willReturn($return)
        ;

        $this->assertEquals($excepted, $this->abstractHcSlave->readRgbLed($this->module));
    }

    public function testReadBufferSize(): void
    {
        $this->prophesizeReadBufferSize($this->module, 4242);

        $this->assertEquals(4242, $this->abstractHcSlave->readBufferSize($this->module));
    }

    private function prophesizeReadBufferSize(Module $module, int $bufferSize): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            216,
            'buffer',
            2
        );
        $this->transformService->asciiToUnsignedInt('buffer')
            ->shouldBeCalledOnce()
            ->willReturn($bufferSize)
        ;
        $this->eventService->fire('', 'readBufferSize', ['bufferSize' => $bufferSize, 'slave' => $module])
            ->shouldBeCalledOnce()
        ;
    }

    public function testReadDeviceId(): void
    {
        $this->prophesizeReadDeviceId($this->module, 4242);

        $this->assertEquals(4242, $this->abstractHcSlave->readDeviceId($this->module));
    }

    private function prophesizeReadDeviceId(Module $module, int $deviceId): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            200,
            'deviceId',
            2
        );
        $this->transformService->asciiToUnsignedInt('deviceId')
            ->shouldBeCalledOnce()
            ->willReturn($deviceId)
        ;
        $this->eventService->fire('', 'readDeviceId', ['deviceId' => $deviceId, 'slave' => $module])
            ->shouldBeCalledOnce()
        ;
    }

    public function testReadEepromFree(): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            213,
            'eepromFree',
            2
        );
        $this->transformService->asciiToUnsignedInt('eepromFree')
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->eventService->fire('', 'readEepromFree', ['eepromFree' => 4242, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(4242, $this->abstractHcSlave->readEepromFree($this->module));
    }

    public function testReadEepromPosition(): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            214,
            'eepromPosition',
            2
        );
        $this->transformService->asciiToUnsignedInt('eepromPosition')
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->eventService->fire('', 'readEepromPosition', ['eepromPosition' => 4242, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(4242, $this->abstractHcSlave->readEepromPosition($this->module));
    }

    public function testReadEepromSize(): void
    {
        $this->prophesizeReadEepromSize($this->module, 4242);

        $this->assertEquals(4242, $this->abstractHcSlave->readEepromSize($this->module));
    }

    private function prophesizeReadEepromSize(Module $module, int $eepromSize): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            212,
            'eepromSize',
            2
        );
        $this->transformService->asciiToUnsignedInt('eepromSize')
            ->shouldBeCalledOnce()
            ->willReturn($eepromSize)
        ;
        $this->eventService->fire('', 'readEepromSize', ['eepromSize' => $eepromSize, 'slave' => $module])
            ->shouldBeCalledOnce()
        ;
    }

    public function testReadHertz(): void
    {
        $this->prophesizeReadHertz($this->module, 42424242);

        $this->assertEquals(42424242, $this->abstractHcSlave->readHertz($this->module));
    }

    private function prophesizeReadHertz(Module $module, int $hertz): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            211,
            'hertz',
            4
        );
        $this->transformService->asciiToUnsignedInt('hertz')
            ->shouldBeCalledOnce()
            ->willReturn($hertz)
        ;
        $this->eventService->fire('', 'readHertz', ['hertz' => $hertz, 'slave' => $module])
            ->shouldBeCalledOnce()
        ;
    }

    public function testReadPwmSpeed(): void
    {
        $this->prophesizeReadPwmSpeed($this->module, 4242);

        $this->assertEquals(4242, $this->abstractHcSlave->readPwmSpeed($this->module));
    }

    private function prophesizeReadPwmSpeed(Module $module, int $pwmSpeed): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            217,
            'pwmSpeed',
            2
        );
        $this->transformService->asciiToUnsignedInt('pwmSpeed')
            ->shouldBeCalledOnce()
            ->willReturn($pwmSpeed)
        ;
        $this->eventService->fire('', 'readPwmSpeed', ['pwmSpeed' => $pwmSpeed, 'slave' => $module])
            ->shouldBeCalledOnce()
        ;
    }

    public function testReadTypeId(): void
    {
        $this->prophesizeReadTypeId($this->module, 42);

        $this->assertEquals(42, $this->abstractHcSlave->readTypeId($this->module));
    }

    private function prophesizeReadTypeId(Module $module, int $typeId): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            201,
            'typeId',
            1
        );
        $this->transformService->asciiToUnsignedInt('typeId', 0)
            ->shouldBeCalledOnce()
            ->willReturn($typeId)
        ;
        $this->eventService->fire('', 'readTypeId', ['slave' => $module, 'typeId' => $typeId])
            ->shouldBeCalledOnce()
        ;
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadPowerLed(bool $on): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            221,
            'powerLed',
            1
        );
        $this->transformService->asciiToUnsignedInt('powerLed')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('', 'readPowerLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readPowerLed($this->module));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadErrorLed(bool $on): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            222,
            'errorLed',
            1
        );
        $this->transformService->asciiToUnsignedInt('errorLed')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('', 'readErrorLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readErrorLed($this->module));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadConnectLed(bool $on): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            223,
            'connectLed',
            1
        );
        $this->transformService->asciiToUnsignedInt('connectLed')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('', 'readConnectLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readConnectLed($this->module));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadTransreceiveLed(bool $on): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            224,
            'transreceiveLed',
            1
        );
        $this->transformService->asciiToUnsignedInt('transreceiveLed')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('', 'readTransreceiveLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readTransreceiveLed($this->module));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadTransceiveLed(bool $on): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            225,
            'transceiveLed',
            1
        );
        $this->transformService->asciiToUnsignedInt('transceiveLed')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('', 'readTransceiveLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readTransceiveLed($this->module));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadReceiveLed(bool $on): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            226,
            'receiveLed',
            1
        );
        $this->transformService->asciiToUnsignedInt('receiveLed')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('', 'readReceiveLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readReceiveLed($this->module));
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadCustomLed(bool $on): void
    {
        AbstractModuleTest::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            227,
            'customLed',
            1
        );
        $this->transformService->asciiToUnsignedInt('customLed')
            ->shouldBeCalledOnce()
            ->willReturn($on ? 1 : 0)
        ;
        $this->eventService->fire('', 'readCustomLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readCustomLed($this->module));
    }

    public function testWriteAddress(): void
    {
        $this->prophesizeWriteAddress(4242, 42, 7);

        $this->abstractHcSlave->writeAddress($this->module, 7);
    }

    private function prophesizeWriteAddress(int $deviceId, int $oldAddress, int $newAddress): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            $oldAddress,
            202,
            chr($deviceId >> 8) . chr($deviceId & 255) . chr($newAddress)
        );
        $this->eventService->fire('', 'beforeWriteAddress', ['newAddress' => $newAddress, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteAddress', ['newAddress' => $newAddress, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->masterService->scanBus($this->master);
    }

    public function testWriteDeviceId(): void
    {
        $this->prophesizeWriteDeviceId($this->module, 4242, 7777);

        $this->abstractHcSlave->writeDeviceId($this->module, 7777);
    }

    private function prophesizeWriteDeviceId(Module $module, int $oldDeviceId, int $newDeviceId): void
    {
        AbstractModuleTest::prophesizeWrite(
            $module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            200,
            chr($oldDeviceId >> 8) . chr($oldDeviceId & 255) . chr($newDeviceId >> 8) . chr($newDeviceId & 255)
        );
        $this->eventService->fire('', 'beforeWriteDeviceId', ['newDeviceId' => $newDeviceId, 'slave' => $module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteDeviceId', ['newDeviceId' => $newDeviceId, 'slave' => $module])
            ->shouldBeCalledOnce()
        ;
    }

    public function testWriteEepromErase(): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            215,
            chr(4242 >> 8) . chr(4242 & 255)
        );
        $this->eventService->fire('', 'beforeWriteEepromErase', ['slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteEepromErase', ['slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeEepromErase($this->module);
    }

    public function testWriteEepromPosition(): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            214,
            chr(4242 >> 8) . chr(4242 & 255)
        );
        $this->eventService->fire('', 'beforeWriteEepromPosition', ['eepromPosition' => 4242, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteEepromPosition', ['eepromPosition' => 4242, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeEepromPosition($this->module, 4242);
    }

    public function testWritePwmSpeed(): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            217,
            chr(4242 >> 8) . chr(4242 & 255)
        );
        $this->eventService->fire('', 'beforeWritePwmSpeed', ['pwmSpeed' => 4242, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWritePwmSpeed', ['pwmSpeed' => 4242, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writePwmSpeed($this->module, 4242);
    }

    public function testWriteRestart(): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            209,
            chr(4242 >> 8) . chr(4242 & 255)
        );
        $this->eventService->fire('', 'beforeWriteRestart', ['slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteRestart', ['slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeRestart($this->module);
    }

    public function testWriteTypeId(): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            201,
            chr(7) . 'a'
        );
        $this->eventService->fire('', 'beforeWriteTypeId', ['typeId' => 7, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteTypeId', ['typeId' => 7, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $type = $this->prophesize(Type::class);
        $type->getId()
            ->shouldBeCalledTimes(5)
            ->willReturn(7)
        ;
        $type->getHelper()
            ->shouldBeCalledOnce()
            ->willReturn('prefect')
        ;
        $slaveService = $this->prophesize(AbstractModule::class);
        $slaveService->handshake($this->module)
            ->shouldBeCalledOnce()
        ;
        $this->moduleFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($slaveService->reveal())
        ;

        $this->abstractHcSlave->writeTypeId($this->module, $type->reveal());
    }

    /**
     * @dataProvider getLedData
     */
    public function testWritePowerLed(bool $on): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            221,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('', 'beforeWritePowerLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWritePowerLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writePowerLed($this->module, $on);
    }

    /**
     * @dataProvider getLedData
     */
    public function testWriteErrorLed(bool $on): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            222,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('', 'beforeWriteErrorLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteErrorLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeErrorLed($this->module, $on);
    }

    /**
     * @dataProvider getLedData
     */
    public function testWriteConnectLed(bool $on): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            223,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('', 'beforeWriteConnectLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteConnectLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeConnectLed($this->module, $on);
    }

    /**
     * @dataProvider getLedData
     */
    public function testWriteTransreceiveLed(bool $on): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            224,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('', 'beforeWriteTransreceiveLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteTransreceiveLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeTransreceiveLed($this->module, $on);
    }

    /**
     * @dataProvider getLedData
     */
    public function testWriteTransceiveLed(bool $on): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            225,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('', 'beforeWriteTransceiveLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteTransceiveLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeTransceiveLed($this->module, $on);
    }

    /**
     * @dataProvider getLedData
     */
    public function testWriteReceiveLed(bool $on): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            226,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('', 'beforeWriteReceiveLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteReceiveLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeReceiveLed($this->module, $on);
    }

    /**
     * @dataProvider getLedData
     */
    public function testWriteCustomLed(bool $on): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            227,
            chr((int) $on) . 'a'
        );
        $this->eventService->fire('', 'beforeWriteCustomLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteCustomLed', ['on' => $on, 'slave' => $this->module])
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeCustomLed($this->module, $on);
    }

    /**
     * @dataProvider getWriteRgbLedData
     */
    public function testWriteRgbLed(string $data, array $leds): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            228,
            $data
        );
        $eventData = [
            'power' => '1',
            'error' => '2',
            'connect' => '3',
            'transceive' => '4',
            'receive' => '5',
            'custom' => '6',
            'slave' => $this->module,
        ];
        $this->eventService->fire('', 'beforeWriteRgbLed', $eventData)
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteRgbLed', $eventData)
            ->shouldBeCalledOnce()
        ;
        $this->transformService->hexToInt('1')
            ->shouldBeCalledOnce()
            ->willReturn($leds['power'])
        ;
        $this->transformService->hexToInt('2')
            ->shouldBeCalledOnce()
            ->willReturn($leds['error'])
        ;
        $this->transformService->hexToInt('3')
            ->shouldBeCalledOnce()
            ->willReturn($leds['connect'])
        ;
        $this->transformService->hexToInt('4')
            ->shouldBeCalledOnce()
            ->willReturn($leds['transceive'])
        ;
        $this->transformService->hexToInt('5')
            ->shouldBeCalledOnce()
            ->willReturn($leds['receive'])
        ;
        $this->transformService->hexToInt('6')
            ->shouldBeCalledOnce()
            ->willReturn($leds['custom'])
        ;

        $this->abstractHcSlave->writeRgbLed(
            $this->module,
            '1',
            '2',
            '3',
            '4',
            '5',
            '6'
        );
    }

    /**
     * @dataProvider getReadAllLedsData
     */
    public function testWriteAllLeds(int $data, array $leds): void
    {
        AbstractModuleTest::prophesizeWrite(
            $this->module->getMaster(),
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            229,
            chr($data) . 'a'
        );
        $this->eventService->fire('', 'beforeWriteAllLeds', array_merge($leds, ['slave' => $this->module]))
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('', 'afterWriteAllLeds', array_merge($leds, ['slave' => $this->module]))
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeAllLeds(
            $this->module,
            $leds['power'],
            $leds['error'],
            $leds['connect'],
            $leds['transreceive'],
            $leds['transceive'],
            $leds['receive'],
            $leds['custom']
        );
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
            [2, $this->getReadAllLedsExcepted(['custom' => true], false, true)],
            [4, $this->getReadAllLedsExcepted(['receive' => true], false, true)],
            [6, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true], false, true)],
            [8, $this->getReadAllLedsExcepted(['transceive' => true], false, true)],
            [10, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true], false, true)],
            [12, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true], false, true)],
            [14, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true], false, true)],
            [16, $this->getReadAllLedsExcepted(['transreceive' => true], false, true)],
            [18, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true], false, true)],
            [20, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true], false, true)],
            [22, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true], false, true)],
            [24, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true], false, true)],
            [26, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true], false, true)],
            [28, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true], false, true)],
            [30, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true], false, true)],
            [32, $this->getReadAllLedsExcepted(['connect' => true], false, true)],
            [34, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true], false, true)],
            [36, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true], false, true)],
            [38, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true], false, true)],
            [40, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true], false, true)],
            [42, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true], false, true)],
            [44, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true], false, true)],
            [46, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true], false, true)],
            [48, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true], false, true)],
            [50, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [52, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [54, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [56, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [58, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [60, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [62, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true], false, true)],
            [64, $this->getReadAllLedsExcepted(['error' => true], false, true)],
            [66, $this->getReadAllLedsExcepted(['custom' => true, 'error' => true], false, true)],
            [68, $this->getReadAllLedsExcepted(['receive' => true, 'error' => true], false, true)],
            [70, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'error' => true], false, true)],
            [72, $this->getReadAllLedsExcepted(['transceive' => true, 'error' => true], false, true)],
            [74, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'error' => true], false, true)],
            [76, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'error' => true], false, true)],
            [78, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'error' => true], false, true)],
            [80, $this->getReadAllLedsExcepted(['transreceive' => true, 'error' => true], false, true)],
            [82, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'error' => true], false, true)],
            [84, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [86, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [88, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [90, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [92, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [94, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true], false, true)],
            [96, $this->getReadAllLedsExcepted(['connect' => true, 'error' => true], false, true)],
            [98, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'error' => true], false, true)],
            [100, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'error' => true], false, true)],
            [102, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'error' => true], false, true)],
            [104, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'error' => true], false, true)],
            [106, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'error' => true], false, true)],
            [108, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'error' => true], false, true)],
            [110, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true], false, true)],
            [112, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [114, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [116, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [118, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [120, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [122, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [124, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [126, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true], false, true)],
            [128, $this->getReadAllLedsExcepted(['power' => true], false, true)],
            [130, $this->getReadAllLedsExcepted(['custom' => true, 'power' => true], false, true)],
            [132, $this->getReadAllLedsExcepted(['receive' => true, 'power' => true], false, true)],
            [134, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'power' => true], false, true)],
            [136, $this->getReadAllLedsExcepted(['transceive' => true, 'power' => true], false, true)],
            [138, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'power' => true], false, true)],
            [140, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'power' => true], false, true)],
            [142, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'power' => true], false, true)],
            [144, $this->getReadAllLedsExcepted(['transreceive' => true, 'power' => true], false, true)],
            [146, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'power' => true], false, true)],
            [148, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [150, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [152, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [154, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [156, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [158, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'power' => true], false, true)],
            [160, $this->getReadAllLedsExcepted(['connect' => true, 'power' => true], false, true)],
            [162, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'power' => true], false, true)],
            [164, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'power' => true], false, true)],
            [166, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'power' => true], false, true)],
            [168, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'power' => true], false, true)],
            [170, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'power' => true], false, true)],
            [172, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'power' => true], false, true)],
            [174, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'power' => true], false, true)],
            [176, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [178, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [180, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [182, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [184, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [186, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [188, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [190, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'power' => true], false, true)],
            [192, $this->getReadAllLedsExcepted(['error' => true, 'power' => true], false, true)],
            [194, $this->getReadAllLedsExcepted(['custom' => true, 'error' => true, 'power' => true], false, true)],
            [196, $this->getReadAllLedsExcepted(['receive' => true, 'error' => true, 'power' => true], false, true)],
            [198, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'error' => true, 'power' => true], false, true)],
            [200, $this->getReadAllLedsExcepted(['transceive' => true, 'error' => true, 'power' => true], false, true)],
            [202, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'error' => true, 'power' => true], false, true)],
            [204, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'error' => true, 'power' => true], false, true)],
            [206, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'error' => true, 'power' => true], false, true)],
            [208, $this->getReadAllLedsExcepted(['transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [210, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [212, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [214, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [216, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [218, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [220, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [222, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'error' => true, 'power' => true], false, true)],
            [224, $this->getReadAllLedsExcepted(['connect' => true, 'error' => true, 'power' => true], false, true)],
            [226, $this->getReadAllLedsExcepted(['custom' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [228, $this->getReadAllLedsExcepted(['receive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [230, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [232, $this->getReadAllLedsExcepted(['transceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [234, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [236, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [238, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [240, $this->getReadAllLedsExcepted(['transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [242, $this->getReadAllLedsExcepted(['custom' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [244, $this->getReadAllLedsExcepted(['receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [246, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [248, $this->getReadAllLedsExcepted(['transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [250, $this->getReadAllLedsExcepted(['custom' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [252, $this->getReadAllLedsExcepted(['receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
            [254, $this->getReadAllLedsExcepted(['custom' => true, 'receive' => true, 'transceive' => true, 'transreceive' => true, 'connect' => true, 'error' => true, 'power' => true], false, true)],
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

    public function getWriteRgbLedData(): array
    {
        return [
            [
                chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0),
                [
                    'power' => 0,
                    'error' => 0,
                    'connect' => 0,
                    'transceive' => 0,
                    'receive' => 0,
                    'custom' => 0,
                ],
            ],
            [
                chr(1) . chr(35) . chr(69) . chr(103) . chr(137) . chr(171) . chr(205) . chr(239) . chr(1),
                [
                    'power' => 18,
                    'error' => 837,
                    'connect' => 1656,
                    'transceive' => 2475,
                    'receive' => 3294,
                    'custom' => 3841,
                ],
            ],
            [
                chr(0) . chr(15) . chr(255) . chr(0) . chr(15) . chr(255) . chr(0) . chr(15) . chr(255),
                [
                    'power' => 0,
                    'error' => 4095,
                    'connect' => 0,
                    'transceive' => 4095,
                    'receive' => 0,
                    'custom' => 4095,
                ],
            ],
        ];
    }

    public function getHandshakeData(): array
    {
        return [
            'With Id' => [7, true, true, 65534],
            'Different Device Id' => [7, false, true, 42420],
            'Existing Slave without Id' => [null, true, true, 42420],
            'Different Device Id without Id' => [null, false, true, 65500],
            'With Id new Slave' => [7, true, false, 42420],
            'Different Device Id new Slave' => [7, false, false, 42420],
            'New Slave without Id' => [null, true, false, 17],
            'Different Device Id without Id new Slave' => [null, false, false, 42420],
            'With Id empty device Id' => [7, true, true, 0],
            'Different Device Id empty device Id' => [7, false, true, 0],
            'Existing Slave without Id empty device Id' => [null, true, true, 0],
            'Different Device Id without Id empty device Id' => [null, false, true, 0],
            'With Id new Slave empty device Id' => [7, true, false, 0],
            'Different Device Id new Slave empty device Id' => [7, false, false, 0],
            'New Slave without Id empty device Id' => [null, true, false, 0],
            'Different Device Id without Id new Slave empty device Id' => [null, false, false, 0],
            'With Id device id to big' => [7, true, true, 65535],
            'Different Device Id device id to big' => [7, false, true, 65535],
            'Existing Slave without Id device id to big' => [null, true, true, 65535],
            'Different Device Id without Id device id to big' => [null, false, true, 65535],
            'With Id new Slave device id to big' => [7, true, false, 65535],
            'Different Device Id new Slave device id to big' => [7, false, false, 65535],
            'New Slave without Id device id to big' => [null, true, false, 65535],
            'Different Device Id without Id new Slave device id to big' => [null, false, false, 65535],
        ];
    }

    public function getHandshakeDataDifferentType(): array
    {
        return [
            'With id' => [7, true, 42420],
            'Different Device Id' => [7, false, 42420],
            'Without Id' => [null, true, 42420],
            'Different Device Id without Id' => [null, false, 1],
            'New Slave' => [7, true, 12],
            'Different Device Id new Slave' => [7, false, 9],
            'Without Id new Slave' => [null, true, 42420],
            'Different Device Id without Id new Slave' => [null, false, 42420],
            'Empty device Id' => [7, true, 0],
            'Different Device Id empty device Id' => [7, false, 0],
            'Without Id empty device Id' => [null, true, 0],
            'Different Device Id without Id empty device Id' => [null, false, 0],
            'New Slave empty device Id' => [7, true, 0],
            'Different Device Id new Slave empty device Id' => [7, false, 0],
            'Without Id new Slave empty device Id' => [null, true, 0],
            'Different Device Id without Id new Slave empty device Id' => [null, false, 0],
            'Device id to big' => [7, true, 65535],
            'Different Device Id device id to big' => [7, false, 65535],
            'Without Id device id to big' => [null, true, 65535],
            'Different Device Id without Id device id to big' => [null, false, 65535],
            'New Slave device id to big' => [7, true, 65535],
            'Different Device Id new Slave device id to big' => [7, false, 65535],
            'Without Id new Slave device id to big' => [null, true, 65535],
            'Different Device Id without Id new Slave device id to big' => [null, false, 65535],
        ];
    }

    public function getHandshakeIllegalDeviceIdData(): array
    {
        return [
            'Device Id 0' => [0],
            'Device Id 65535' => [65535],
            'Device Id 100000' => [100000],
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
