<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Service;

use Codeception\Test\Unit;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Factory\ProtocolFactory;
use GibsonOS\Module\Hc\Mapper\MasterMapper;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;
use GibsonOS\Module\Hc\Service\SenderService;
use GibsonOS\Test\Unit\Core\ModelManagerTrait;
use Prophecy\Prophecy\ObjectProphecy;

class SenderServiceTest extends Unit
{
    use ModelManagerTrait;

    private SenderService $senderService;

    private ObjectProphecy|MasterMapper $masterMapper;

    private ObjectProphecy|ProtocolFactory $protocolFactory;

    protected function _before(): void
    {
        $this->loadModelManager();

        $this->masterMapper = $this->prophesize(MasterMapper::class);
        $this->protocolFactory = $this->prophesize(ProtocolFactory::class);
        $this->senderService = new SenderService(
            $this->masterMapper->reveal(),
            $this->protocolFactory->reveal()
        );
    }

    public function testSend(): void
    {
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $busMessage = new BusMessage('42.42.42.42', 255);
        $protocolService->send($busMessage)
            ->shouldBeCalledOnce()
        ;
        $this->protocolFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($protocolService->reveal())
        ;

        $this->senderService->send($busMessage, 'prefect');
    }

    /**
     * @dataProvider getReceiveReadDataData
     */
    public function testReceiveReadData(string $address, int $type): void
    {
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $busMessage = new BusMessage($address, $type);
        $protocolService->receiveReadData(420042)
            ->shouldBeCalledOnce()
            ->willReturn($busMessage)
        ;
        $this->protocolFactory->get('galaxy')
            ->shouldBeCalledOnce()
            ->willReturn($protocolService->reveal())
        ;

        if ($address !== '42.42.42.42') {
            $this->expectException(ReceiveError::class);
        } elseif ($type !== 7) {
            $this->expectException(ReceiveError::class);
        }

        $master = (new Master($this->modelWrapper->reveal()))
            ->setProtocol('galaxy')
            ->setSendPort(420042)
            ->setAddress('42.42.42.42')
        ;

        $this->assertEquals($busMessage, $this->senderService->receiveReadData($master, 7));
    }

    public function testReceiveReceiveReturn(): void
    {
        $master = (new Master($this->modelWrapper->reveal()))
            ->setProtocol('prefect')
            ->setAddress('42.42.42.42')
        ;
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $busMessage = new BusMessage('42.42.42.42', 255);
        $protocolService->receiveReceiveReturn($busMessage)
            ->shouldBeCalledOnce()
        ;
        $this->protocolFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($protocolService->reveal())
        ;

        $this->senderService->receiveReceiveReturn($master, $busMessage);
    }

    public function getReceiveReadDataData(): array
    {
        return [
            'Wrong address' => ['7.7.7.7', 7],
            'Wrong type' => ['42.42.42.42', 42],
            'All ok' => ['42.42.42.42', 7],
        ];
    }
}
