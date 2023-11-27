<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc;

use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Mapper\MasterMapper;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Service\Protocol\UdpService;
use GibsonOS\Test\Functional\Core\FunctionalTest;
use Prophecy\Prophecy\ObjectProphecy;

class HcFunctionalTest extends FunctionalTest
{
    protected ObjectProphecy|UdpService $udpService;

    private array $readReturns = [];

    private array $writeMessages = [];

    private array $readMessages = [];

    protected function _before(): void
    {
        parent::_before();

        $this->readReturns = [];
        $this->writeMessages = [];
        $this->readMessages = [];
        $this->udpService = $this->prophesize(UdpService::class);
        $this->serviceManager->setService(UdpService::class, $this->udpService->reveal());
    }

    protected function getDir(): string
    {
        return __DIR__;
    }

    protected function addModule(Type $type): Module
    {
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $master = (new Master($this->modelWrapper))
            ->setName('galaxy')
            ->setAddress('42.42.42.42')
            ->setSendPort(420042)
            ->setProtocol('udp')
            ->setSendPort(42);
        $modelManager->saveWithoutChildren($master);
        $modelManager->saveWithoutChildren($type);
        $module = (new Module($this->modelWrapper))
            ->setName('marvin')
            ->setAddress(42)
            ->setDeviceId(4242)
            ->setId(7)
            ->setType($type)
            ->setMaster($master)
            ->setConfig(json_encode([
                'temperature' => [4, 2, 42],
                'pressure' => [1, 2, 3, 4, 5, 6, 7, 8, 9],
                'humidity' => [6, 5, 4, 3, 2, 1],
            ]));
        $modelManager->saveWithoutChildren($module);

        return $module;
    }

    protected function prophesizeWrite(Module $module, int $command, string $data, int $type = 255): void
    {
        $master = $module->getMaster();
        $busMessage = (new BusMessage($master->getAddress(), $type))
            ->setCommand($command)
            ->setSlaveAddress($module->getAddress())
            ->setWrite(true)
            ->setPort($master->getSendPort())
            ->setData($data)
        ;
        $this->writeMessages[] = $busMessage;
        $this->udpService->send($busMessage)
            ->shouldBeCalledTimes(count(array_filter(
                $this->writeMessages,
                fn (BusMessage $writeMessage): bool => $writeMessage == $busMessage,
            )))
        ;
        $this->udpService->receiveReceiveReturn($busMessage)
            ->shouldBeCalledOnce()
        ;
    }

    protected function prophesizeReadMaster(Master $master, string $data = null, int $type = 255): void
    {
        $busMessage = (new BusMessage($master->getAddress(), $type))
            ->setPort($master->getSendPort())
            ->setData($data)
        ;
        $this->writeMessages[] = $busMessage;
        $this->udpService->send($busMessage)
            ->shouldBeCalledTimes(count(array_filter(
                $this->writeMessages,
                fn (BusMessage $writeMessage): bool => $writeMessage == $busMessage,
            )))
        ;
        $this->udpService->receiveReceiveReturn($busMessage)
            ->shouldBeCalledOnce()
        ;
    }

    protected function prophesizeRead(
        Module $module,
        int $command,
        int $length,
        string $data = '',
        int $type = 255,
    ): void {
        $masterMapper = $this->serviceManager->get(MasterMapper::class);
        $master = $module->getMaster();
        $busMessage = (new BusMessage($master->getAddress(), $type))
            ->setCommand($command)
            ->setSlaveAddress($module->getAddress())
            ->setPort($master->getSendPort())
            ->setData(chr($length))
        ;
        $this->readMessages[] = $busMessage;
        $this->udpService->send($busMessage)
            ->shouldBeCalledTimes(count(array_filter(
                $this->readMessages,
                fn (BusMessage $readMessage): bool => $readMessage == $busMessage,
            )))
        ;
        $receiveBusMessage = (new BusMessage($master->getAddress(), $type))
            ->setData(chr($module->getAddress()) . chr($command) . $data)
            ->setSlaveAddress($module->getAddress())
        ;
        $receiveBusMessage->setChecksum($masterMapper->getChecksum($receiveBusMessage));
        $this->readReturns[] = $receiveBusMessage;
        $this->udpService->receiveReadData($master->getSendPort())
            ->shouldBeCalledTimes(count($this->readReturns))
            ->willReturn(...$this->readReturns)
        ;
    }
}
