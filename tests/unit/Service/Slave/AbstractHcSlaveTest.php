<?php declare(strict_types=1);

namespace Gibson\Test\Unit\Service\Slave;

use Codeception\Test\Unit;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
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
        $this->slave = $this->prophesize(Module::class);
        $this->master = $this->prophesize(Master::class);

        $this->abstractHcSlave = new class($this->masterService->reveal(), $this->transformService, $this->eventService->reveal(), $this->moduleRepository->reveal(), $this->typeRepository->reveal(), $this->masterRepository->reveal(), $this->logRepository->reveal(), $this->slaveFactory->reveal(), $this->slave->reveal()) extends AbstractHcSlave {
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

            public function slaveHandshake(Module $slave): Module
            {
                return $slave;
            }

            public function onOverwriteExistingSlave(Module $slave, Module $existingSlave): Module
            {
                return $slave;
            }

            public function receive(Module $slave, int $type, int $command, string $data): void
            {
            }
        };
    }

    public function testHandshakeExistingDevice(): void
    {
        $this->prophesizeReadTypeId(7);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;

        $this->prophesizeReadDeviceId($this->slave, 4242);
        $this->slave->getDeviceId()
            ->shouldBeCalledTimes(3)
            ->willReturn(4242)
        ;

        $this->slave->getId()
            ->shouldBeCalledTimes(1)
            ->willReturn(7)
        ;
        $this->prophesizeHandshake($this->slave);

        $this->masterService->send($this->master->reveal(), 4, chr(42))
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReceiveReturn($this->master->reveal())
            ->shouldBeCalledOnce()
        ;

        $this->slave->getMaster()
            ->shouldBeCalledTimes(20)
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(19)
        ;

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }

    public function testHandshakeWrongType(): void
    {
        $this->prophesizeReadTypeId(42);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;

        $type = $this->prophesize(Type::class);
        $type->getHelper()
            ->shouldBeCalledOnce()
            ->willReturn('prefect')
        ;
        $this->slave->setType($type->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;
        $this->slave->getType()
            ->shouldBeCalledOnce()
            ->willReturn($type->reveal())
        ;
        $this->typeRepository->getById(42)
            ->shouldBeCalledOnce()
            ->willReturn($type->reveal())
        ;
        $slaveService = $this->prophesize(AbstractSlave::class);
        $slaveService->handshake($this->slave->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;
        $this->slaveFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($slaveService->reveal())
        ;

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }

    public function testHandshakeWrongDeviceId(): void
    {
        $this->prophesizeReadTypeId(7);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;

        $this->prophesizeReadDeviceId($this->slave, 4200);
        $this->slave->getDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;

        $newSlave = $this->prophesize(Module::class);
        $this->prophesizeHandshake($newSlave);
        $newSlave->getDeviceId()
            ->shouldBeCalledTimes(2)
            ->willReturn(4242)
        ;
        $newSlave->getMaster()
            ->shouldBeCalledTimes(14)
        ;
        $newSlave->getAddress()
            ->shouldBeCalledTimes(13)
        ;

        $this->moduleRepository->getByDeviceId(4200)
            ->shouldBeCalledOnce()
            ->willReturn($newSlave->reveal())
        ;

        $this->masterService->send($this->master->reveal(), 4, chr(42))
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReceiveReturn($this->master->reveal())
            ->shouldBeCalledOnce()
        ;

        $this->slave->getMaster()
            ->shouldBeCalledTimes(6)
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(6)
        ;

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }

    public function testHandshakeEmptyId(): void
    {
        $this->prophesizeReadTypeId(7);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;

        $this->prophesizeReadDeviceId($this->slave, 4242);
        $this->slave->getId()
            ->shouldBeCalledTimes(1)
            ->willReturn(null)
        ;

        $newSlave = $this->prophesize(Module::class);
        $this->prophesizeHandshake($newSlave);
        $newSlave->getDeviceId()
            ->shouldBeCalledTimes(2)
            ->willReturn(4242)
        ;
        $newSlave->getMaster()
            ->shouldBeCalledTimes(14)
        ;
        $newSlave->getAddress()
            ->shouldBeCalledTimes(13)
        ;

        $this->moduleRepository->getByDeviceId(4242)
            ->shouldBeCalledOnce()
            ->willReturn($newSlave)
        ;

        $this->masterService->send($this->master->reveal(), 4, chr(42))
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReceiveReturn($this->master->reveal())
            ->shouldBeCalledOnce()
        ;

        $this->slave->getMaster()
            ->shouldBeCalledTimes(6)
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(6)
        ;
        $this->slave->getDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }

    /**
     * @dataProvider getHandshakeIllegalDeviceIdData
     */
    public function testHandshakeIllegalDeviceId(int $deviceId): void
    {
        $this->prophesizeReadTypeId(7);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;

        $this->prophesizeReadDeviceId($this->slave, $deviceId);
        $this->prophesizeHandshake($this->slave);
        $this->prophesizeWriteDeviceId($this->slave, $deviceId, 4242);

        $this->masterService->send($this->master->reveal(), 4, chr(42))
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReceiveReturn($this->master->reveal())
            ->shouldBeCalledTimes(2)
        ;

        $this->moduleRepository->getFreeDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242);

        $this->slave->getDeviceId()
            ->shouldBeCalledTimes($deviceId === 0 ? 3 : 4)
            ->willReturn($deviceId)
        ;
        $this->slave->getId()
            ->shouldBeCalledOnce()
            ->willReturn(42424242)
        ;
        $this->slave->getMaster()
            ->shouldBeCalledTimes(23)
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(21)
        ;

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }

    /**
     * @dataProvider getHandshakeIllegalDeviceIdData
     */
    public function testHandshakeIllegalDeviceIdWrongDeviceIdModule(int $deviceId): void
    {
        $this->prophesizeReadTypeId(7);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;

        $this->prophesizeReadDeviceId($this->slave, 42420);
        $this->prophesizeHandshake($this->slave);
        $this->prophesizeWriteDeviceId($this->slave, $deviceId, 4242);

        $this->masterService->send($this->master->reveal(), 4, chr(42))
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReceiveReturn($this->master->reveal())
            ->shouldBeCalledTimes(2)
        ;

        $this->moduleRepository->getFreeDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->moduleRepository->getByDeviceId(42420)
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;

        $this->slave->getDeviceId()
            ->shouldBeCalledTimes($deviceId === 0 ? 3 : 4)
            ->willReturn($deviceId)
        ;
        $this->slave->getMaster()
            ->shouldBeCalledTimes(23)
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(21)
        ;

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }

    /**
     * @dataProvider getHandshakeIllegalDeviceIdData
     */
    public function testHandshakeIllegalDeviceIdWrongDeviceIdDatabase(int $deviceId): void
    {
        $this->prophesizeReadTypeId(7);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;

        $this->prophesizeReadDeviceId($this->slave, $deviceId);
        $this->prophesizeHandshake($this->slave);
        $this->prophesizeWriteDeviceId($this->slave, $deviceId, 4242);

        $this->masterService->send($this->master->reveal(), 4, chr(42))
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReceiveReturn($this->master->reveal())
            ->shouldBeCalledTimes(2)
        ;

        $this->moduleRepository->getFreeDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->moduleRepository->getByDeviceId($deviceId)
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;

        $this->slave->getDeviceId()
            ->shouldBeCalledTimes($deviceId === 0 ? 3 : 4)
            ->willReturn(42420, $deviceId, $deviceId, $deviceId)
        ;
        $this->slave->setDeviceId(4242)
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;
        $this->slave->getMaster()
            ->shouldBeCalledTimes(23)
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(21)
        ;

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }

    /**
     * @dataProvider getHandshakeIllegalDeviceIdData
     */
    public function testHandshakeIllegalDeviceIdEmptyId(int $deviceId): void
    {
        $this->prophesizeReadTypeId(7);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;

        $this->prophesizeReadDeviceId($this->slave, $deviceId);
        $this->prophesizeHandshake($this->slave);
        $this->prophesizeWriteDeviceId($this->slave, $deviceId, 4242);

        $this->masterService->send($this->master->reveal(), 4, chr(42))
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReceiveReturn($this->master->reveal())
            ->shouldBeCalledTimes(2)
        ;

        $this->moduleRepository->getFreeDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->moduleRepository->getByDeviceId($deviceId)
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;

        $this->slave->getDeviceId()
            ->shouldBeCalledTimes($deviceId === 0 ? 3 : 4)
            ->willReturn($deviceId)
        ;
        $this->slave->getId()
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;
        $this->slave->getMaster()
            ->shouldBeCalledTimes(23)
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(21)
        ;

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }

    public function testHandshakeWrongDeviceIdNewSlave(): void
    {
        $this->prophesizeReadTypeId(7);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;

        $this->prophesizeReadDeviceId($this->slave, 4200);
        $this->prophesizeWriteAddress(4242, 42, 42);
        $this->prophesizeHandshake($this->slave);

        $this->masterService->send($this->master->reveal(), 4, chr(42))
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReceiveReturn($this->master->reveal())
            ->shouldBeCalledTimes(2)
        ;

        $this->moduleRepository->getByDeviceId(4200)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;
        $this->masterRepository->getNextFreeAddress(42424242)
            ->shouldBeCalledOnce()
            ->willReturn(42)
        ;

        $this->master->getId()
            ->shouldBeCalledOnce()
            ->willReturn(42424242)
        ;

        $this->slave->setDeviceId(4200)
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;
        $this->slave->getDeviceId()
            ->shouldBeCalledTimes(4)
            ->willReturn(4242)
        ;
        $this->slave->getMaster()
            ->shouldBeCalledTimes(25)
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(21)
        ;

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }

    public function testHandshakeEmptyIdNewSlave(): void
    {
        $this->prophesizeReadTypeId(7);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;

        $this->prophesizeReadDeviceId($this->slave, 4242);
        $this->prophesizeWriteAddress(4242, 42, 42);
        $this->prophesizeHandshake($this->slave);

        $this->masterService->send($this->master->reveal(), 4, chr(42))
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReceiveReturn($this->master->reveal())
            ->shouldBeCalledTimes(2)
        ;

        $this->moduleRepository->getByDeviceId(4242)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;
        $this->masterRepository->getNextFreeAddress(42424242)
            ->shouldBeCalledOnce()
            ->willReturn(42)
        ;

        $this->master->getId()
            ->shouldBeCalledOnce()
            ->willReturn(42424242)
        ;

        $this->slave->setDeviceId(4242)
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;
        $this->slave->getDeviceId()
            ->shouldBeCalledTimes(4)
            ->willReturn(4242)
        ;
        $this->slave->getId()
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;
        $this->slave->getMaster()
            ->shouldBeCalledTimes(25)
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(21)
        ;

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }

    /**
     * @dataProvider getHandshakeIllegalDeviceIdData
     */
    public function testHandshakeWrongAndIllegalDeviceIdNewSlave(int $deviceId): void
    {
        $this->prophesizeReadTypeId(7);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;

        $this->prophesizeReadDeviceId($this->slave, 4242);
        $this->prophesizeHandshake($this->slave);
        $this->prophesizeWriteDeviceId($this->slave, $deviceId, 4242);
        $this->prophesizeWriteAddress(4242, 42, 42);

        $this->masterService->send($this->master->reveal(), 4, chr(42))
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReceiveReturn($this->master->reveal())
            ->shouldBeCalledTimes(3)
        ;

        $this->moduleRepository->getFreeDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->moduleRepository->getByDeviceId(4242)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;
        $this->masterRepository->getNextFreeAddress(42424242)
            ->shouldBeCalledOnce()
            ->willReturn(42)
        ;

        $this->master->getId()
            ->shouldBeCalledOnce()
            ->willReturn(42424242)
        ;

        $this->slave->getDeviceId()
            ->shouldBeCalledTimes($deviceId === 0 ? 4 : 5)
            ->willReturn($deviceId, $deviceId, $deviceId, $deviceId === 0 ? 4242 : $deviceId, 4242)
        ;
        $this->slave->setDeviceId(4242)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->slave->reveal())
        ;
        $this->slave->getMaster()
            ->shouldBeCalledTimes(28)
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(23)
        ;

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }

    /**
     * @dataProvider getHandshakeIllegalDeviceIdData
     */
    public function testHandshakeIllegalDeviceIdEmptyIdNewSlave(int $deviceId): void
    {
        $this->prophesizeReadTypeId(7);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn(7)
        ;

        $this->prophesizeReadDeviceId($this->slave, $deviceId);
        $this->prophesizeHandshake($this->slave);
        $this->prophesizeWriteDeviceId($this->slave, $deviceId, 4242);
        $this->prophesizeWriteAddress(4242, 42, 42);

        $this->masterService->send($this->master->reveal(), 4, chr(42))
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReceiveReturn($this->master->reveal())
            ->shouldBeCalledTimes(3)
        ;

        $this->moduleRepository->getFreeDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn(4242)
        ;
        $this->moduleRepository->getByDeviceId($deviceId)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;
        $this->masterRepository->getNextFreeAddress(42424242)
            ->shouldBeCalledOnce()
            ->willReturn(42)
        ;

        $this->master->getId()
            ->shouldBeCalledOnce()
            ->willReturn(42424242)
        ;

        $this->slave->setDeviceId($deviceId)
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;
        $this->slave->getDeviceId()
            ->shouldBeCalledTimes($deviceId === 0 ? 4 : 5)
            ->willReturn($deviceId, $deviceId, $deviceId, $deviceId === 0 ? 4242 : $deviceId, 4242)
        ;
        $this->slave->setDeviceId(4242)
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;
        $this->slave->getId()
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;
        $this->slave->getMaster()
            ->shouldBeCalledTimes(28)
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(23)
        ;

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }

    private function prophesizeHandshake(ObjectProphecy $slave): void
    {
        $this->prophesizeReadHertz($slave, 420000);
        $slave->setHertz(420000)
            ->shouldBeCalledOnce()
            ->willReturn($slave->reveal())
        ;
        $this->prophesizeReadBufferSize($slave, 4);
        $slave->setBufferSize(4)
            ->shouldBeCalledOnce()
            ->willReturn($slave->reveal())
        ;
        $this->prophesizeReadEepromSize($slave, 420042);
        $slave->setEepromSize(420042)
            ->shouldBeCalledOnce()
            ->willReturn($slave->reveal())
        ;
        $this->prophesizeReadPwmSpeed($slave, 42042042);
        $slave->setPwmSpeed(42042042)
            ->shouldBeCalledOnce()
            ->willReturn($slave->reveal())
        ;
    }

    /**
     * @dataProvider getHandshakeData
     */
    /*public function testHandshake(?int $id, bool $typeEqual, bool $deviceIdEqual, bool $exists, int $deviceId): void
    {
        $this->prophesizeReadTypeId(7);
        $this->slave->getTypeId()
            ->shouldBeCalledOnce()
            ->willReturn($typeEqual ? 7 : 9)
        ;

        if ($typeEqual) {
            $this->prophesizeReadDeviceId($deviceId);
            $this->slave->getDeviceId()
                ->shouldBeCalledTimes(3)
                ->willReturn($deviceIdEqual ? $deviceId : $deviceId + 1)
            ;
            $this->prophesizeReadHertz(420000);
            $this->slave->setHertz(420000)
                ->shouldBeCalledOnce()
                ->willReturn($this->slave->reveal())
            ;
            $this->prophesizeReadBufferSize(4);
            $this->slave->setBufferSize(4)
                ->shouldBeCalledOnce()
                ->willReturn($this->slave->reveal())
            ;
            $this->prophesizeReadEepromSize(420042);
            $this->slave->setEepromSize(420042)
                ->shouldBeCalledOnce()
                ->willReturn($this->slave->reveal())
            ;
            $this->prophesizeReadPwmSpeed(42042042);
            $this->slave->setPwmSpeed(42042042)
                ->shouldBeCalledOnce()
                ->willReturn($this->slave->reveal())
            ;
            $this->slave->getAddress()
                ->shouldBeCalledTimes(19)
            ;
            $this->slave->getMaster()
                ->shouldBeCalledTimes(20)
                ->willReturn($this->master->reveal())
            ;

            if ($deviceIdEqual) {
                $this->slave->getId()
                    ->shouldBeCalledOnce()
                    ->willReturn($id)
                ;
            }

            if (!$deviceIdEqual || $id === null) {
                $getByDeviceIdCall = $this->moduleRepositroy->getByDeviceId($deviceId)
                    ->shouldBeCalledOnce()
                ;

                if ($exists) {
                    $this->masterService->send($this->master->reveal(), 4, chr(42))
                        ->shouldBeCalledOnce()
                    ;
                    $this->masterService->receiveReceiveReturn($this->master->reveal())
                        ->shouldBeCalledOnce()
                    ;
                    $getByDeviceIdCall->willReturn($this->slave->reveal());
                } else {
                    $getByDeviceIdCall->willThrow(SelectError::class);
                    $this->slave->setDeviceId($deviceId)
                        ->shouldBeCalledOnce()
                        ->willReturn($this->slave->reveal())
                    ;
                    $this->prophesizeWriteAddress($deviceIdEqual ? $deviceId : $deviceId + 1, 42, 88);
                    $this->master->getId()
                        ->shouldBeCalledOnce()
                        ->willReturn(79)
                    ;
                    $this->masterRepository->getNextFreeAddress(79)
                        ->shouldBeCalledOnce()
                        ->willReturn(88)
                    ;
                    $this->slave->getAddress()
                        ->shouldBeCalledTimes(20)
                    ;
                    $this->slave->getMaster()
                        ->shouldBeCalledTimes(23)
                    ;
                    $this->slave->getDeviceId()
                        ->shouldBeCalledTimes(4)
                    ;
                }
            } else {
                $this->masterService->send($this->master->reveal(), 4, chr(42))
                    ->shouldBeCalledOnce()
                ;
                $this->masterService->receiveReceiveReturn($this->master->reveal())
                    ->shouldBeCalledOnce()
                ;
            }

            $this->slave->getDeviceId()
                ->willReturn($deviceIdEqual ? $deviceId : $deviceId + 1)
            ;

            if ($deviceId === 0 || $deviceId > 65534) {
                var_dump('hier');
                $this->moduleRepositroy->getFreeDeviceId()
                    ->shouldBeCalledOnce()
                    ->willReturn($deviceId)
                ;
                $this->slave->setDeviceId($deviceId)
                    ->shouldBeCalledOnce()
                    ->willReturn($this->slave->reveal())
                ;
                $this->prophesizeWriteDeviceId($deviceId, $deviceId);
                $this->masterService->receiveReceiveReturn($this->master->reveal())
                    ->shouldBeCalledTimes(2)
                ;
                $this->slave->getMaster()
                    ->shouldBeCalledTimes(23)
                ;
                $this->slave->getAddress()
                    ->shouldBeCalledTimes(21)
                ;
                $this->slave->getDeviceId()
                    ->shouldBeCalledTimes(3)
                ;
                $this->slave->setDeviceId($deviceId)
                    ->shouldBeCalledTimes(2)
                ;
            }
        } else {
            $type = $this->prophesize(Type::class);
            $type->getHelper()
                ->shouldBeCalledOnce()
                ->willReturn('prefect')
            ;
            $this->slave->setType($type->reveal())
                ->shouldBeCalledOnce()
                ->willReturn($this->slave->reveal())
            ;
            $this->slave->getType()
                ->shouldBeCalledOnce()
                ->willReturn($type->reveal())
            ;
            $this->typeRepository->getById(7)
                ->shouldBeCalledOnce()
                ->willReturn($type->reveal())
            ;
            $slaveService = $this->prophesize(AbstractSlave::class);
            $slaveService->handshake($this->slave->reveal())
                ->shouldBeCalledOnce()
                ->willReturn($this->slave->reveal())
            ;
            $this->slaveFactory->get('prefect')
                ->shouldBeCalledOnce()
                ->willReturn($slaveService->reveal())
            ;
        }

        $this->abstractHcSlave->handshake($this->slave->reveal());
    }*/

    /**
     * @dataProvider getReadAllLedsData
     */
    public function testReadAllLeds(int $return, array $excepted): void
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
            229,
            'allLeds',
            1
        );
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
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            220,
            'ledStatus',
            1
        );
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
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            228,
            'rgbLed',
            9
        );
        $this->eventService->fire('readRgbLed', array_merge($excepted, ['slave' => $this->slave->reveal()]))
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($excepted, $this->abstractHcSlave->readRgbLed($this->slave->reveal()));
    }

    public function testReadBufferSize(): void
    {
        $this->prophesizeReadBufferSize($this->slave, 4242);

        $this->assertEquals(4242, $this->abstractHcSlave->readBufferSize($this->slave->reveal()));
    }

    private function prophesizeReadBufferSize(ObjectProphecy $slave, int $bufferSize): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            216,
            'buffer',
            2
        );
        $this->eventService->fire('readBufferSize', ['bufferSize' => $bufferSize, 'slave' => $slave->reveal()])
            ->shouldBeCalledOnce()
        ;
    }

    public function testReadDeviceId(): void
    {
        $this->prophesizeReadDeviceId($this->slave, 4242);

        $this->assertEquals(4242, $this->abstractHcSlave->readDeviceId($this->slave->reveal()));
    }

    private function prophesizeReadDeviceId(ObjectProphecy $slave, int $deviceId): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            200,
            'deviceId',
            2
        );
        $this->eventService->fire('readDeviceId', ['deviceId' => $deviceId, 'slave' => $slave->reveal()])
            ->shouldBeCalledOnce()
        ;
    }

    public function testReadEepromFree(): void
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
            213,
            'eepromFree',
            2
        );
        $this->eventService->fire('readEepromFree', ['eepromFree' => 4242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(4242, $this->abstractHcSlave->readEepromFree($this->slave->reveal()));
    }

    public function testReadEepromPosition(): void
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
            214,
            'eepromPosition',
            2
        );
        $this->eventService->fire('readEepromPosition', ['eepromPosition' => 4242, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(4242, $this->abstractHcSlave->readEepromPosition($this->slave->reveal()));
    }

    public function testReadEepromSize(): void
    {
        $this->prophesizeReadEepromSize($this->slave, 4242);

        $this->assertEquals(4242, $this->abstractHcSlave->readEepromSize($this->slave->reveal()));
    }

    private function prophesizeReadEepromSize(ObjectProphecy $slave, int $eepromSize): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            212,
            'eepromSize',
            2
        );
        $this->eventService->fire('readEepromSize', ['eepromSize' => $eepromSize, 'slave' => $slave->reveal()])
            ->shouldBeCalledOnce()
        ;
    }

    public function testReadHertz(): void
    {
        $this->prophesizeReadHertz($this->slave, 42424242);

        $this->assertEquals(42424242, $this->abstractHcSlave->readHertz($this->slave->reveal()));
    }

    private function prophesizeReadHertz(ObjectProphecy $slave, int $hertz): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            211,
            'hertz',
            4
        );
        $this->eventService->fire('readHertz', ['hertz' => $hertz, 'slave' => $slave->reveal()])
            ->shouldBeCalledOnce()
        ;
    }

    public function testReadPwmSpeed(): void
    {
        $this->prophesizeReadPwmSpeed($this->slave, 4242);

        $this->assertEquals(4242, $this->abstractHcSlave->readPwmSpeed($this->slave->reveal()));
    }

    private function prophesizeReadPwmSpeed(ObjectProphecy $slave, int $pwmSpeed): void
    {
        AbstractSlaveTest::prophesizeRead(
            $this->master,
            $slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            217,
            'pwmSpeed',
            2
        );
        $this->eventService->fire('readPwmSpeed', ['pwmSpeed' => $pwmSpeed, 'slave' => $slave->reveal()])
            ->shouldBeCalledOnce()
        ;
    }

    public function testReadTypeId(): void
    {
        $this->prophesizeReadTypeId(42);

        $this->assertEquals(42, $this->abstractHcSlave->readTypeId($this->slave->reveal()));
    }

    private function prophesizeReadTypeId(int $typeId): void
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
            201,
            'typeId',
            1
        );
        $this->eventService->fire('readTypeId', ['typeId' => $typeId, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
    }

    /**
     * @dataProvider getLedData
     */
    public function testReadPowerLed(bool $on): void
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
            221,
            'powerLed',
            1
        );
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
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            222,
            'errorLed',
            1
        );
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
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            223,
            'connectLed',
            1
        );
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
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            224,
            'transreceiveLed',
            1
        );
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
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            225,
            'transceiveLed',
            1
        );
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
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            226,
            'receiveLed',
            1
        );
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
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            227,
            'customLed',
            1
        );
        $this->eventService->fire('readCustomLed', ['on' => $on, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals($on, $this->abstractHcSlave->readCustomLed($this->slave->reveal()));
    }

    public function testWriteAddress(): void
    {
        $this->prophesizeWriteAddress(4242, 42, 7);

        $this->abstractHcSlave->writeAddress($this->slave->reveal(), 7);
    }

    private function prophesizeWriteAddress(int $deviceId, int $oldAddress, int $newAddress): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            $oldAddress,
            202,
            chr($deviceId >> 8) . chr($deviceId & 255) . chr($newAddress)
        );
        $this->slave->getDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn($deviceId)
        ;
        $this->slave->setAddress($newAddress)
            ->shouldBeCalledOnce()
            ->willReturn($this->slave->reveal())
        ;
        $this->eventService->fire('beforeWriteAddress', ['newAddress' => $newAddress, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteAddress', ['newAddress' => $newAddress, 'slave' => $this->slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->slave->getMaster()->shouldBeCalledTimes(4);
        $this->masterService->scanBus($this->master->reveal());
    }

    public function testWriteDeviceId(): void
    {
        $this->prophesizeWriteDeviceId($this->slave, 4242, 7777);

        $this->abstractHcSlave->writeDeviceId($this->slave->reveal(), 7777);
    }

    private function prophesizeWriteDeviceId(ObjectProphecy $slave, int $oldDeviceId, int $newDeviceId): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->master,
            $slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            200,
            chr($oldDeviceId >> 8) . chr($oldDeviceId & 255) . chr($newDeviceId >> 8) . chr($newDeviceId & 255)
        );
        $slave->getDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn($oldDeviceId)
        ;
        $slave->setDeviceId($newDeviceId)
            ->shouldBeCalledOnce()
            ->willReturn($slave->reveal())
        ;
        $this->eventService->fire('beforeWriteDeviceId', ['newDeviceId' => $newDeviceId, 'slave' => $slave->reveal()])
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteDeviceId', ['newDeviceId' => $newDeviceId, 'slave' => $slave->reveal()])
            ->shouldBeCalledOnce()
        ;
    }

    public function testWriteEepromErase(): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->master,
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
            $this->master,
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
            $this->master,
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
            $this->master,
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
            $this->master,
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
            $this->master,
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
            $this->master,
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
            $this->master,
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
            $this->master,
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
            $this->master,
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
            $this->master,
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
            $this->master,
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

    /**
     * @dataProvider getWriteRgbLedData
     */
    public function testWriteRgbLed(string $data, array $leds): void
    {
        AbstractSlaveTest::prophesizeWrite(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
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
            'slave' => $this->slave->reveal(),
        ];
        $this->eventService->fire('beforeWriteRgbLed', $eventData)
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteRgbLed', $eventData)
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeRgbLed(
            $this->slave->reveal(),
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
        AbstractSlaveTest::prophesizeWrite(
            $this->master,
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            229,
            chr($data) . 'a'
        );
        $this->eventService->fire('beforeWriteAllLeds', array_merge($leds, ['slave' => $this->slave->reveal()]))
            ->shouldBeCalledOnce()
        ;
        $this->eventService->fire('afterWriteAllLeds', array_merge($leds, ['slave' => $this->slave->reveal()]))
            ->shouldBeCalledOnce()
        ;

        $this->abstractHcSlave->writeAllLeds(
            $this->slave->reveal(),
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
        // ?int $id, bool $typeEqual, bool $deviceIdEqual, bool $exists, int $deviceIds
        return [
            'With Id' => [7, true, true, true, 65534],
            'Different Type Id' => [7, false, true, true, 42420],
            'Different Device Id' => [7, true, false, true, 42420],
            'Different Type and Device Id' => [7, false, false, true, 42420],
            'Existing Slave without Id' => [null, true, true, true, 42420],
            'Different Type Id without Id' => [null, false, true, true, 42420],
            'Different Device Id without Id' => [null, true, false, true, 65500],
            'Different Type and Device Id without Id' => [null, false, false, true, 1],
            'With Id new Slave' => [7, true, true, false, 42420],
            'Different Type Id new Slave' => [7, false, true, false, 12],
            'Different Device Id new Slave' => [7, true, false, false, 42420],
            'Different Type and Device Id new Slave' => [7, false, false, false, 9],
            'New Slave without Id' => [null, true, true, false, 17],
            'Different Type Id without Id new Slave' => [null, false, true, false, 42420],
            'Different Device Id without Id new Slave' => [null, true, false, false, 42420],
            'Different Type and Device Id without Id new Slave' => [null, false, false, false, 42420],
            'With Id empty device Id' => [7, true, true, true, 0],
            'Different Type Id empty device Id' => [7, false, true, true, 0],
            'Different Device Id empty device Id' => [7, true, false, true, 0],
            'Different Type and Device Id empty device Id' => [7, false, false, true, 0],
            'Existing Slave without Id empty device Id' => [null, true, true, true, 0],
            'Different Type Id without Id empty device Id' => [null, false, true, true, 0],
            'Different Device Id without Id empty device Id' => [null, true, false, true, 0],
            'Different Type and Device Id without Id empty device Id' => [null, false, false, true, 0],
            'With Id new Slave empty device Id' => [7, true, true, false, 0],
            'Different Type Id new Slave empty device Id' => [7, false, true, false, 0],
            'Different Device Id new Slave empty device Id' => [7, true, false, false, 0],
            'Different Type and Device Id new Slave empty device Id' => [7, false, false, false, 0],
            'New Slave without Id empty device Id' => [null, true, true, false, 0],
            'Different Type Id without Id new Slave empty device Id' => [null, false, true, false, 0],
            'Different Device Id without Id new Slave empty device Id' => [null, true, false, false, 0],
            'Different Type and Device Id without Id new Slave empty device Id' => [null, false, false, false, 0],
            'With Id device id to big' => [7, true, true, true, 65535],
            'Different Type Id device id to big' => [7, false, true, true, 65535],
            'Different Device Id device id to big' => [7, true, false, true, 65535],
            'Different Type and Device Id device id to big' => [7, false, false, true, 65535],
            'Existing Slave without Id device id to big' => [null, true, true, true, 65535],
            'Different Type Id without Id device id to big' => [null, false, true, true, 65535],
            'Different Device Id without Id device id to big' => [null, true, false, true, 65535],
            'Different Type and Device Id without Id device id to big' => [null, false, false, true, 65535],
            'With Id new Slave device id to big' => [7, true, true, false, 65535],
            'Different Type Id new Slave device id to big' => [7, false, true, false, 65535],
            'Different Device Id new Slave device id to big' => [7, true, false, false, 65535],
            'Different Type and Device Id new Slave device id to big' => [7, false, false, false, 65535],
            'New Slave without Id device id to big' => [null, true, true, false, 65535],
            'Different Type Id without Id new Slave device id to big' => [null, false, true, false, 65535],
            'Different Device Id without Id new Slave device id to big' => [null, true, false, false, 65535],
            'Different Type and Device Id without Id new Slave device id to big' => [null, false, false, false, 65535],
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
