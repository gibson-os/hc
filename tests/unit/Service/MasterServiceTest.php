<?php
declare(strict_types=1);

namespace Service;

use Codeception\Test\Unit;
use DateTime;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\EventService;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\SenderService;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;
use GibsonOS\Module\Hc\Service\Slave\AbstractSlave;
use GibsonOS\Module\Hc\Service\TransformService;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class MasterServiceTest extends Unit
{
    /**
     * @var ObjectProphecy|SenderService
     */
    private $senderService;

    /**
     * @var ObjectProphecy|EventService
     */
    private $eventService;

    /**
     * @var ObjectProphecy|TransformService
     */
    private $transformService;

    /**
     * @var ObjectProphecy|ModuleRepository
     */
    private $moduleRepository;

    /**
     * @var ObjectProphecy|TypeRepository
     */
    private $typeRepository;

    /**
     * @var ObjectProphecy|SlaveFactory
     */
    private $slaveFactory;

    /**
     * @var ObjectProphecy|LogRepository
     */
    private $logRepository;

    /**
     * @var MasterService
     */
    private $masterService;

    protected function _before(): void
    {
        $this->senderService = $this->prophesize(SenderService::class);
        $this->eventService = $this->prophesize(EventService::class);
        $this->transformService = $this->prophesize(TransformService::class);
        $this->slaveFactory = $this->prophesize(SlaveFactory::class);
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->moduleRepository = $this->prophesize(ModuleRepository::class);
        $this->typeRepository = $this->prophesize(TypeRepository::class);
        $this->masterService = new MasterService(
            $this->senderService->reveal(),
            $this->eventService->reveal(),
            $this->transformService->reveal(),
            $this->slaveFactory->reveal(),
            $this->logRepository->reveal(),
            $this->moduleRepository->reveal(),
            $this->typeRepository->reveal()
        );
    }

    /**
     * @dataProvider getReceiveData
     */
    public function testReceive(
        string $data,
        string $cleanData,
        int $type,
        int $address,
        ?int $command,
        ?Module $actualSlave,
        Module $expectedSlave
    ): void {
        $this->transformService->asciiToHex($cleanData)
            ->shouldBeCalledOnce()
            ->willReturn('Handtuch')
        ;

        /** @var Log|ObjectProphecy $log */
        $log = $this->prophesize(Log::class);
        $log->setMaster($expectedSlave->getMaster())->willReturn($log->reveal());
        $log->setModule($expectedSlave)->willReturn($log->reveal());
        $log->save()->shouldBeCalledOnce();

        $this->logRepository->create($type, 'Handtuch', 'input')
            ->shouldBeCalledOnce()
            ->willReturn($log->reveal())
        ;
        $this->transformService->asciiToUnsignedInt($data, 0)
            ->shouldBeCalledOnce()
            ->willReturn($address)
        ;

        $getByAddressCall = $this->moduleRepository->getByAddress($address, (int) $expectedSlave->getMaster()->getId())
            ->shouldBeCalledOnce()
        ;

        if ($actualSlave === null) {
            $getByAddressCall->willThrow(SelectError::class);
        } else {
            $getByAddressCall->willReturn($actualSlave);
        }

        /** @var AbstractHcSlave|ObjectProphecy $slaveService */
        $slaveService = $this->prophesize(AbstractHcSlave::class);
        $getSlaveFactoryCall = $this->slaveFactory->get($expectedSlave->getType()->getHelper())
            ->shouldBeCalledOnce()
            ->willReturn($slaveService->reveal())
        ;

        if ($type === 3) {
            if ($expectedSlave->getTypeId() === 0) {
                $getSlaveFactoryCall->shouldNotBeCalled();
            }

            if ($actualSlave === null) {
                $getByDefaultAddressCall = $this->typeRepository->getByDefaultAddress($address)->shouldBeCalledOnce();

                if ($expectedSlave->getTypeId() === 0) {
                    $getByDefaultAddressCall->willThrow(SelectError::class);
                    $this->expectException(SelectError::class);
                    $log->save()->shouldNotBeCalled();
                } else {
                    $getByDefaultAddressCall->willReturn($expectedSlave->getType());
                    $this->moduleRepository->create('Neues Modul', $expectedSlave->getType())
                        ->shouldBeCalledOnce()
                        ->willReturn($expectedSlave)
                    ;
                }
            }

            if ($expectedSlave->getTypeId() !== 0) {
                $slaveService->handshake($expectedSlave)
                    ->shouldBeCalledOnce()
                    ->willReturn($expectedSlave)
                ;
            }
        } else {
            if ($actualSlave === null) {
                $this->expectException(SelectError::class);
                $log->save()->shouldNotBeCalled();
                $getSlaveFactoryCall->shouldNotBeCalled();
            } else {
                if ($expectedSlave->getTypeId() === 0) {
                    $getSlaveFactoryCall->willReturn($this->prophesize(AbstractSlave::class));
                    $this->expectException(ReceiveError::class);
                    $log->save()->shouldNotBeCalled();
                } else {
                    $slaveService->receive($expectedSlave, $type, $command, $cleanData)
                        ->shouldBeCalledOnce()
                    ;
                    $log->setCommand($command)
                        ->shouldBeCalledOnce()
                        ->willReturn($log->reveal())
                    ;
                }
            }

            $this->transformService->asciiToUnsignedInt($data, 1)
                ->shouldBeCalledOnce()
                ->willReturn($command)
            ;
        }

        $this->masterService->receive($expectedSlave->getMaster(), $type, $data);
    }

    public function testSend(): void
    {
        $master = $this->prophesize(Master::class);
        $this->senderService->send($master, 42, 'Herz aus Gold')
            ->shouldBeCalledOnce()
        ;

        $this->masterService->send($master->reveal(), 42, 'Herz aus Gold');
    }

    public function testSetAddress(): void
    {
        $master = $this->prophesize(Master::class);
        $master->getName()
            ->shouldBeCalledOnce()
            ->willReturn('Marvin')
        ;
        $master->setAddress(42)
            ->shouldBeCalledOnce()
            ->willReturn($master->reveal())
        ;
        $master->save()
            ->shouldBeCalledOnce()
        ;
        $this->senderService->send($master->reveal(), 1, 'Marvin*')
            ->shouldBeCalledOnce()
        ;
        $this->senderService->receiveReceiveReturn($master->reveal())
            ->shouldBeCalledOnce()
        ;

        $this->transformService->asciiToHex('Marvin*')
            ->shouldBeCalledOnce()
            ->willReturn('End in tears')
        ;
        $log = $this->prophesize(Log::class);
        $log->setMaster($master)
            ->shouldBeCalledOnce()
            ->willReturn($log->reveal())
        ;
        $log->save()
            ->shouldBeCalledOnce()
        ;
        $this->logRepository->create(1, 'End in tears', 'output')
            ->shouldBeCalledOnce()
            ->willReturn($log->reveal())
        ;

        $this->masterService->setAddress($master->reveal(), 42);
    }

    public function testScanBus(): void
    {
        $master = $this->prophesize(Master::class);
        $this->senderService->send($master->reveal(), 5, '')
            ->shouldBeCalledOnce()
        ;
        $this->senderService->receiveReceiveReturn($master->reveal())
            ->shouldBeCalledOnce()
        ;

        $this->masterService->scanBus($master->reveal());
    }

    /**
     * @dataProvider getReceiveReadData
     */
    public function testReceiveReadData(int $address, int $command): void
    {
        $master = $this->prophesize(Master::class);
        $this->senderService->receiveReadData($master->reveal(), 255)
            ->shouldBeCalledOnce()
            ->willReturn('**Handtuch')
        ;
        $this->transformService->asciiToUnsignedInt('**Handtuch', 0)
            ->shouldBeCalledOnce()
            ->willReturn($address)
        ;

        if ($address !== 42) {
            $this->expectException(ReceiveError::class);
        } else {
            $this->transformService->asciiToUnsignedInt('**Handtuch', 1)
                ->shouldBeCalledOnce()
                ->willReturn($command)
            ;

            if ($command !== 7) {
                $this->expectException(ReceiveError::class);
            }
        }

        $this->assertEquals(
            $this->masterService->receiveReadData($master->reveal(), 42, 255, 7),
            'Handtuch'
        );
    }

    public function getReceiveReadData(): array
    {
        return [
            'All OK' => [42, 7],
            'Wrong Address' => [0, 7],
            'Wrong Command' => [42, 0],
            'All Wrong' => [0, 0],
        ];
    }

    public function getReceiveData(): array
    {
        /** @var Master|ObjectProphecy $slave */
        $master = $this->prophesize(Master::class);
        $master->getId()->willReturn(4200);

        /** @var Type|ObjectProphecy $slave */
        $type = $this->prophesize(Type::class);
        $type->getId()->willReturn(4242);
        $type->getName()->willReturn('Ford Prefect');
        $type->getHelper()->willReturn('ford');

        /** @var Module|ObjectProphecy $slave */
        $slave = $this->prophesize(Module::class);
        $slave->getMaster()->willReturn($master->reveal());
        $slave->getType()->willReturn($type->reveal());
        $slave->getId()->willReturn(420);
        $slave->getTypeId()->willReturn(4242);
        $slave->getMasterId()->willReturn(4200);
        $slave->setOffline(false)->willReturn($slave->reveal());
        $slave->setModified(Argument::type(DateTime::class))->willReturn($slave->reveal());
        $slave->setAddress(42)->willReturn($slave->reveal());
        $slave->setMaster($master->reveal())->willReturn($slave->reveal());

        /** @var Module|ObjectProphecy $slaveUnknownType */
        $slaveUnknownType = $this->prophesize(Module::class);
        $slaveUnknownType->getMaster()->willReturn($master->reveal());
        $slaveUnknownType->getType()->willReturn($type->reveal());
        $slaveUnknownType->getId()->willReturn(420);
        $slaveUnknownType->getTypeId()->willReturn(0);
        $slaveUnknownType->getName()->willReturn('Marvin');

        return [
            'Handshake with existing slave' => [
                chr(7) . chr(0),
                '',
                3,
                42,
                null,
                $slave->reveal(),
                $slave->reveal(),
            ],
            'Handshake with new slave' => [
                chr(7) . chr(0),
                '',
                3,
                42,
                null,
                null,
                $slave->reveal(),
            ],
            'Handshake with new slave and unknown type' => [
                chr(7) . chr(0),
                '',
                3,
                42,
                null,
                null,
                $slaveUnknownType->reveal(),
            ],
            'Receive hc slave data unknown slave' => [
                chr(7) . chr(0),
                '',
                255,
                42,
                201,
                null,
                $slave->reveal(),
            ],
            'Receive hc slave data unknown slave type' => [
                chr(7) . chr(0),
                '',
                255,
                42,
                201,
                $slaveUnknownType->reveal(),
                $slaveUnknownType->reveal(),
            ],
            'Receive hc slave data' => [
                chr(7) . chr(0),
                '',
                255,
                42,
                201,
                $slave->reveal(),
                $slave->reveal(),
            ],
        ];
    }
}
