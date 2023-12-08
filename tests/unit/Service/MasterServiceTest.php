<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Service;

use Codeception\Test\Unit;
use DateTime;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\DateTimeService;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Enum\Direction;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Mapper\MasterMapper;
use GibsonOS\Module\Hc\Model\Log;
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
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;
use GibsonOS\Module\Hc\Service\SenderService;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\Test\Unit\Core\ModelManagerTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class MasterServiceTest extends Unit
{
    use ModelManagerTrait;

    private ObjectProphecy|SenderService $senderService;

    private ObjectProphecy|EventService $eventService;

    private TransformService|ObjectProphecy $transformService;

    private ObjectProphecy|ModuleRepository $moduleRepository;

    private ObjectProphecy|TypeRepository $typeRepository;

    private ObjectProphecy|ModuleFactory $slaveFactory;

    private MasterService|ObjectProphecy $masterService;

    private ObjectProphecy|MasterRepository $masterRepository;

    private MasterMapper|ObjectProphecy $masterMapper;

    private ObjectProphecy|LogRepository $logRepository;

    private LoggerInterface|ObjectProphecy $loggerInterface;

    private ObjectProphecy|DateTimeService $dateTimeService;

    protected function _before(): void
    {
        $this->loadModelManager();
        $this->senderService = $this->prophesize(SenderService::class);
        $this->transformService = $this->prophesize(TransformService::class);
        $this->slaveFactory = $this->prophesize(ModuleFactory::class);
        $this->masterMapper = $this->prophesize(MasterMapper::class);
        $this->moduleRepository = $this->prophesize(ModuleRepository::class);
        $this->masterRepository = $this->prophesize(MasterRepository::class);
        $this->typeRepository = $this->prophesize(TypeRepository::class);
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->loggerInterface = $this->prophesize(LoggerInterface::class);
        $this->dateTimeService = $this->prophesize(DateTimeService::class);

        $this->masterService = new MasterService(
            $this->senderService->reveal(),
            $this->slaveFactory->reveal(),
            $this->masterMapper->reveal(),
            $this->logRepository->reveal(),
            $this->moduleRepository->reveal(),
            $this->typeRepository->reveal(),
            $this->loggerInterface->reveal(),
            $this->masterRepository->reveal(),
            $this->dateTimeService->reveal(),
            $this->modelManager->reveal(),
            $this->modelWrapper->reveal(),
        );
    }

    public function testReceiveNewSlaveWithoutAddress(): void
    {
        $this->logRepository->create(3, '', Direction::INPUT)
            ->shouldBeCalledOnce()
            ->willReturn(new Log($this->modelWrapper->reveal()))
        ;

        $this->expectException(ReceiveError::class);
        $this->masterService->receive(new Master($this->modelWrapper->reveal()), new BusMessage('42.42.42.42', 3));
    }

    public function testReceiveWithoutCommand(): void
    {
        $this->logRepository->create(255, '', Direction::INPUT)
            ->shouldBeCalledOnce()
            ->willReturn(new Log($this->modelWrapper->reveal()))
        ;

        $this->expectException(ReceiveError::class);
        $this->masterService->receive(new Master($this->modelWrapper->reveal()), new BusMessage('42.42.42.42', 255));
    }

    public function testReceive(): void
    {
        $module = (new Module($this->modelWrapper->reveal()))
            ->setType((new Type($this->modelWrapper->reveal()))->setHelper('prefect'))
        ;
        $this->logRepository->create(255, '', Direction::INPUT)
            ->shouldBeCalledOnce()
            ->willReturn(new Log($this->modelWrapper->reveal()))
        ;
        $this->moduleRepository->getByAddress(42, 7)
            ->shouldBeCalledOnce()
            ->willReturn($module)
        ;
        $busMessage = (new BusMessage('42.42.42.42', 255))
            ->setCommand(24)
            ->setSlaveAddress(42)
        ;
        /** @var ObjectProphecy|AbstractHcModule $moduleService */
        $moduleService = $this->prophesize(AbstractHcModule::class);
        $moduleService->receive($module, $busMessage)->shouldBeCalledOnce();
        $this->slaveFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($moduleService->reveal())
        ;
        $this->modelManager->saveWithoutChildren(Argument::any())->shouldBeCalledTimes(3);
        $this->modelManager->saveWithoutChildren($module)->shouldBeCalledOnce();
        $this->modelManager->saveWithoutChildren(Argument::type(Log::class))->shouldBeCalledOnce();

        $this->masterService->receive((new Master($this->modelWrapper->reveal()))->setId(7), $busMessage);
    }

    public function testReceiveWithoutSlaveAddress(): void
    {
        $this->logRepository->create(255, '', Direction::INPUT)
            ->shouldBeCalledOnce()
            ->willReturn(new Log($this->modelWrapper->reveal()))
        ;

        $busMessage = (new BusMessage('42.42.42.42', 255))
            ->setCommand(24)
        ;
        $this->expectException(ReceiveError::class);

        $this->masterService->receive((new Master($this->modelWrapper->reveal()))->setId(7), $busMessage);
    }

    public function testReceiveWithoutSlave(): void
    {
        $busMessage = (new BusMessage('42.42.42.42', 255))
            ->setCommand(24)
            ->setSlaveAddress(42)
        ;
        $this->moduleRepository->getByAddress(42, 7)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;
        $this->logRepository->create(255, '', Direction::INPUT)
            ->shouldBeCalledOnce()
            ->willReturn(new Log($this->modelWrapper->reveal()))
        ;
        $this->expectException(SelectError::class);

        $this->masterService->receive((new Master($this->modelWrapper->reveal()))->setId(7), $busMessage);
    }

    public function testReceiveNoHcModule(): void
    {
        $module = (new Module($this->modelWrapper->reveal()))
            ->setName('Marvin')
            ->setType((new Type($this->modelWrapper->reveal()))->setName('Ford')->setHelper('prefect'))
        ;
        $this->moduleRepository->getByAddress(42, 7)
            ->shouldBeCalledOnce()
            ->willReturn($module)
        ;
        $busMessage = (new BusMessage('42.42.42.42', 255))
            ->setCommand(24)
            ->setSlaveAddress(42)
        ;
        /** @var ObjectProphecy|AbstractModule $moduleService */
        $moduleService = $this->prophesize(AbstractModule::class);
        $this->slaveFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($moduleService->reveal())
        ;
        $this->logRepository->create(255, '', Direction::INPUT)
            ->shouldBeCalledOnce()
            ->willReturn(new Log($this->modelWrapper->reveal()))
        ;
        $this->expectException(ReceiveError::class);

        $this->masterService->receive((new Master($this->modelWrapper->reveal()))->setId(7), $busMessage);
    }

    public function testReceiveExistingSlave(): void
    {
        $module = (new Module($this->modelWrapper->reveal()))->setType((new Type($this->modelWrapper->reveal()))->setHelper('prefect'));
        $this->moduleRepository->getByAddress(42, 4200)
            ->shouldBeCalledOnce()
            ->willReturn($module)
        ;
        /** @var ObjectProphecy|AbstractModule $moduleService */
        $moduleService = $this->prophesize(AbstractModule::class);
        $moduleService->handshake($module)
            ->shouldBeCalledOnce()
            ->willReturn($module)
        ;
        $this->slaveFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($moduleService->reveal())
        ;
        $this->logRepository->create(3, '', Direction::INPUT)
            ->shouldBeCalledOnce()
            ->willReturn(new Log($this->modelWrapper->reveal()))
        ;
        $this->modelManager->saveWithoutChildren(Argument::any())->shouldBeCalledTimes(3);
        $this->modelManager->saveWithoutChildren(Argument::type(Module::class))->shouldBeCalledOnce();
        $this->modelManager->saveWithoutChildren(Argument::type(Log::class))->shouldBeCalledOnce();

        $this->masterService->receive((new Master($this->modelWrapper->reveal()))->setId(4200), (new BusMessage('42.42.42.42', 3))->setSlaveAddress(42));
    }

    public function testReceiveNewSlave(): void
    {
        $type = (new Type($this->modelWrapper->reveal()))->setHelper('prefect');
        $module = (new Module($this->modelWrapper->reveal()))->setType($type);
        $this->moduleRepository->getByAddress(42, 4200)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;
        /** @var ObjectProphecy|AbstractModule $moduleService */
        $moduleService = $this->prophesize(AbstractModule::class);
        $moduleService->handshake(Argument::type(Module::class))
            ->shouldBeCalledOnce()
            ->willReturn($module)
        ;
        $this->slaveFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($moduleService->reveal())
        ;
        $this->typeRepository->getByDefaultAddress(42)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;
        $this->typeRepository->getByHelperName('blank')
            ->shouldBeCalledOnce()
            ->willReturn($type)
        ;
        $this->logRepository->create(3, '', Direction::INPUT)
            ->shouldBeCalledOnce()
            ->willReturn(new Log($this->modelWrapper->reveal()))
        ;
        $this->modelManager->saveWithoutChildren(Argument::any())->shouldBeCalledTimes(3);
        $this->modelManager->saveWithoutChildren(Argument::type(Module::class))->shouldBeCalledOnce();
        $this->modelManager->saveWithoutChildren(Argument::type(Log::class))->shouldBeCalledOnce();

        $master = (new Master($this->modelWrapper->reveal()))
            ->setId(4200)
            ->setAddress('42.42.42.42')
        ;
        $this->masterService->receive($master, (new BusMessage('42.42.42.42', 3))->setSlaveAddress(42));
    }

    public function testReceiveNewSlaveWithDefaultAddress(): void
    {
        $type = (new Type($this->modelWrapper->reveal()))->setHelper('prefect');
        $module = (new Module($this->modelWrapper->reveal()))->setType($type);
        $this->moduleRepository->getByAddress(42, 4200)
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;
        /** @var ObjectProphecy|AbstractModule $moduleService */
        $moduleService = $this->prophesize(AbstractModule::class);
        $moduleService->handshake(Argument::type(Module::class))
            ->shouldBeCalledOnce()
            ->willReturn($module)
        ;
        $this->slaveFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($moduleService->reveal())
        ;
        $this->typeRepository->getByDefaultAddress(42)
            ->shouldBeCalledOnce()
            ->willReturn($type)
        ;
        $this->logRepository->create(3, '', Direction::INPUT)
            ->shouldBeCalledOnce()
            ->willReturn(new Log($this->modelWrapper->reveal()))
        ;
        $this->modelManager->saveWithoutChildren(Argument::any())->shouldBeCalledTimes(3);
        $this->modelManager->saveWithoutChildren(Argument::type(Module::class))->shouldBeCalledOnce();
        $this->modelManager->saveWithoutChildren(Argument::type(Log::class))->shouldBeCalledOnce();

        $master = (new Master($this->modelWrapper->reveal()))
            ->setId(4200)
            ->setAddress('42.42.42.42')
        ;
        $this->masterService->receive($master, (new BusMessage('42.42.42.42', 3))->setSlaveAddress(42));
    }

    public function testHandshake(): void
    {
        /** @var ObjectProphecy|ProtocolInterface $protocolService */
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $protocolService->getName()
            ->shouldBeCalledOnce()
            ->willReturn('galaxy')
        ;
        $master = (new Master($this->modelWrapper->reveal()))
            ->setSendPort(420042)
            ->setProtocol('galaxy')
        ;
        $this->masterRepository->getByName('Marvin', 'galaxy')
            ->shouldBeCalledOnce()
            ->willReturn($master)
        ;
        $this->dateTimeService->get()
            ->shouldBeCalledOnce()
            ->willReturn(new DateTime())
        ;
        $busMessage = (new BusMessage('42.42.42.42', 255))
            ->setData('Marvin')
        ;
        $this->modelManager->saveWithoutChildren(Argument::any())->shouldBeCalledOnce();
        $this->modelManager->saveWithoutChildren($master)->shouldBeCalledOnce();

        $this->masterService->handshake($protocolService->reveal(), $busMessage);
    }

    public function testHandshakeNoData(): void
    {
        /** @var ObjectProphecy|ProtocolInterface $protocolService */
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $protocolService->getName()
            ->shouldBeCalledOnce()
            ->willReturn('galaxy')
        ;
        $busMessage = (new BusMessage('42.42.42.42', 255));

        $this->expectException(GetError::class);

        $this->masterService->handshake($protocolService->reveal(), $busMessage);
    }

    public function testHandshakeNewMaster(): void
    {
        /** @var ObjectProphecy|ProtocolInterface $protocolService */
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $protocolService->getName()
            ->shouldBeCalledOnce()
            ->willReturn('galaxy')
        ;
        $master = (new Master($this->modelWrapper->reveal()))
            ->setAddress('42.42.42.42')
            ->setSendPort(420042)
            ->setProtocol('galaxy')
        ;
        $this->masterRepository->getByName('Marvin', 'galaxy')
            ->shouldBeCalledOnce()
            ->willThrow(SelectError::class)
        ;
        $this->masterRepository->add('Marvin', 'galaxy', '42.42.42.42')
            ->shouldBeCalledOnce()
            ->willReturn($master)
        ;
        $busMessage = (new BusMessage('42.42.42.42', 255))
            ->setData('Marvin')
        ;

        $this->masterService->handshake($protocolService->reveal(), $busMessage);
    }

    public function testSend(): void
    {
        $master = (new Master($this->modelWrapper->reveal()))->setProtocol('galaxy');
        $busMessage = (new BusMessage('42.42.42.42', MasterService::TYPE_DATA))
            ->setData('Herz aus Gold')
        ;
        $this->senderService->send($busMessage, 'galaxy')
            ->shouldBeCalledOnce()
        ;

        $this->masterService->send($master, $busMessage);
    }

    public function testScanBus(): void
    {
        $master = (new Master($this->modelWrapper->reveal()))
            ->setAddress('42.42.42.42s')
            ->setSendPort(420042)
            ->setProtocol('galaxy')
        ;
        $this->senderService->send(Argument::type(BusMessage::class), 'galaxy')
            ->shouldBeCalledOnce()
        ;
        $this->senderService->receiveReceiveReturn($master, Argument::type(BusMessage::class))
            ->shouldBeCalledOnce()
        ;

        $this->masterService->scanBus($master);
    }

    /**
     * @dataProvider getReceiveReadData
     */
    public function testReceiveReadData(int $address, int $command): void
    {
        $master = (new Master($this->modelWrapper->reveal()))
            ->setAddress('42.42.42.42')
        ;
        $expectedBusMessage = (new BusMessage('42.42.42.42', 255))
            ->setSlaveAddress(42)
            ->setCommand(7)
            ->setData(chr(42) . chr(7) . 'Handtuch')
        ;
        $this->senderService->receiveReadData($master, 255)
            ->shouldBeCalledOnce()
            ->willReturn($expectedBusMessage)
        ;

        if ($address !== 42) {
            $this->expectException(ReceiveError::class);
        } else {
            if ($command !== 7) {
                $this->expectException(ReceiveError::class);
            }
        }

        $busMessage = (new BusMessage('42.42.42.42', 255))
            ->setSlaveAddress($address)
            ->setCommand($command)
        ;

        $this->assertEquals(
            $expectedBusMessage,
            $this->masterService->receiveReadData($master, $busMessage)
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
}
