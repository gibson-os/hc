<?php declare(strict_types=1);

namespace Service;

use Codeception\Test\Unit;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Factory\ProtocolFactory;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Service\Formatter\MasterFormatter;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;
use GibsonOS\Module\Hc\Service\SenderService;
use GibsonOS\Module\Hc\Service\TransformService;
use Prophecy\Prophecy\ObjectProphecy;

class SenderServiceTest extends Unit
{
    /**
     * @var SenderService
     */
    private $senderService;

    /**
     * @var ObjectProphecy|MasterFormatter
     */
    private $masterFormatter;

    /**
     * @var ObjectProphecy|TransformService
     */
    private $transformService;

    /**
     * @var ObjectProphecy|MasterRepository
     */
    private $masterRepository;

    /**
     * @var ObjectProphecy|ProtocolFactory
     */
    private $protocolFactory;

    /**
     * @var ObjectProphecy|Master
     */
    private $master;

    protected function _before(): void
    {
        $this->masterFormatter = $this->prophesize(MasterFormatter::class);
        $this->transformService = new TransformService();
        $this->masterRepository = $this->prophesize(MasterRepository::class);
        $this->protocolFactory = $this->prophesize(ProtocolFactory::class);
        $this->senderService = new SenderService(
            $this->masterFormatter->reveal(),
            $this->transformService,
            $this->masterRepository->reveal(),
            $this->protocolFactory->reveal()
        );
        $this->master = $this->prophesize(Master::class);
    }

    public function testSend(): void
    {
        $this->master->getProtocol()
            ->shouldBeCalledOnce()
            ->willReturn('prefect')
        ;
        $this->master->getAddress()
            ->shouldBeCalledOnce()
            ->willReturn(42)
        ;
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $protocolService->send(7, 'Handtuch', 42)
            ->shouldBeCalledOnce()
        ;
        $this->protocolFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($protocolService->reveal())
        ;

        $this->senderService->send($this->master->reveal(), 7, 'Handtuch');
    }

    /**
     * @dataProvider getReceiveReadDataData
     */
    public function testReceiveReadData(int $address, int $type): void
    {
        $this->master->getProtocol()
            ->shouldBeCalledOnce()
            ->willReturn('prefect')
        ;
        $this->master->getAddress()
            ->shouldBeCalledOnce()
            ->willReturn(42)
        ;
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $protocolService->receiveReadData()
            ->shouldBeCalledOnce()
            ->willReturn('Handtuch')
        ;
        $this->protocolFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($protocolService->reveal())
        ;
        $this->masterFormatter->getMasterAddress('Handtuch')
            ->shouldBeCalledOnce()
            ->willReturn($address)
        ;
        $this->masterFormatter->checksumEqual('Handtuch')
            ->shouldBeCalledOnce()
        ;

        if ($address !== 42) {
            $this->expectException(ReceiveError::class);
        } else {
            $this->masterFormatter->getType('Handtuch')
                ->shouldBeCalledOnce()
                ->willReturn($type)
            ;

            if ($type !== 7) {
                $this->expectException(ReceiveError::class);
            }
        }

        $this->assertEquals('Handtuch', $this->senderService->receiveReadData($this->master->reveal(), 7));
    }

    public function testReceiveReceiveReturn(): void
    {
        $this->master->getProtocol()
            ->shouldBeCalledOnce()
            ->willReturn('prefect')
        ;
        $this->master->getAddress()
            ->shouldBeCalledOnce()
            ->willReturn(42)
        ;
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $protocolService->receiveReceiveReturn(42)
            ->shouldBeCalledOnce()
        ;
        $this->protocolFactory->get('prefect')
            ->shouldBeCalledOnce()
            ->willReturn($protocolService->reveal())
        ;

        $this->senderService->receiveReceiveReturn($this->master->reveal());
    }

    public function getReceiveReadDataData(): array
    {
        return [
            'Wrong address' => [7, 7],
            'Wrong type' => [42, 42],
            'All ok' => [42, 7],
        ];
    }
}
