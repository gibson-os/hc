<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Service;

use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Mapper\MasterMapper;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;
use GibsonOS\Module\Hc\Service\ReceiverService;
use GibsonOS\UnitTest\AbstractTest;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class ReceiverServiceTest extends AbstractTest
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy|MasterService
     */
    private $masterService;

    /**
     * @var ObjectProphecy|MasterMapper
     */
    private $masterMapper;

    /**
     * @var ObjectProphecy|MasterRepository
     */
    private $masterRepository;

    /**
     * @var ReceiverService
     */
    private $receiverService;

    protected function _before(): void
    {
        $this->masterService = $this->prophesize(MasterService::class);
        $this->masterMapper = $this->prophesize(MasterMapper::class);
        $this->masterRepository = $this->prophesize(MasterRepository::class);
        $this->receiverService = new ReceiverService(
            $this->masterService->reveal(),
            $this->masterMapper->reveal(),
            $this->masterRepository->reveal(),
            $this->serviceManager->get(LoggerInterface::class)
        );
    }

    public function testReceiveNoData(): void
    {
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $protocolService->receive()
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $this->receiverService->receive($protocolService->reveal());
    }

    public function testReceiveDataEmpty(): void
    {
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $busMessage = new BusMessage('42.42.42.42', 255);
        $protocolService->receive()
            ->shouldBeCalledOnce()
            ->willReturn($busMessage)
        ;

        $this->receiverService->receive($protocolService->reveal());
    }

    public function testReceiveDataEmptyString(): void
    {
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $busMessage = (new BusMessage('42.42.42.42', 255))->setData('');
        $protocolService->receive()
            ->shouldBeCalledOnce()
            ->willReturn($busMessage)
        ;

        $this->receiverService->receive($protocolService->reveal());
    }

    public function testReceiveChecksumNotEqual(): void
    {
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $busMessage = (new BusMessage('42.42.42.42', MasterService::TYPE_HANDSHAKE))
            ->setData('Arthur')
        ;
        $protocolService->receive()
            ->shouldBeCalledOnce()
            ->willReturn($busMessage)
        ;
        $this->masterMapper->checksumEqual($busMessage)
            ->shouldBeCalledOnce()
            ->willThrow(ReceiveError::class)
        ;
        $this->expectException(ReceiveError::class);

        $this->receiverService->receive($protocolService->reveal());
    }

    public function testReceiveHandshake(): void
    {
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $busMessage = (new BusMessage('42.42.42.42', MasterService::TYPE_HANDSHAKE))
            ->setData('Arthur')
        ;
        $protocolService->receive()
            ->shouldBeCalledOnce()
            ->willReturn($busMessage)
        ;
        $this->masterService->handshake($protocolService->reveal(), $busMessage)
            ->shouldBeCalledOnce()
        ;
        $this->masterMapper->checksumEqual($busMessage)
            ->shouldBeCalledOnce()
        ;

        $this->receiverService->receive($protocolService->reveal());
    }

    public function testReceiveData(): void
    {
        $protocolService = $this->prophesize(ProtocolInterface::class);
        $busMessage = (new BusMessage('42.42.42.42', 255))
            ->setData('Arthur')
        ;
        $protocolService->receive()
            ->shouldBeCalledOnce()
            ->willReturn($busMessage)
        ;
        $protocolService->getName()
            ->shouldBeCalledOnce()
            ->willReturn('galaxy')
        ;
        $this->masterMapper->checksumEqual($busMessage)
            ->shouldBeCalledOnce()
        ;
        $master = new Master();
        $this->masterRepository->getByAddress('42.42.42.42', 'galaxy')
            ->shouldBeCalledOnce()
            ->willReturn($master)
        ;
        $this->masterMapper->extractSlaveDataFromMessage($busMessage)
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receive($master, $busMessage)
            ->shouldBeCalledOnce()
        ;

        $this->receiverService->receive($protocolService->reveal());
    }
}
