<?php
declare(strict_types=1);

namespace Gibson\Test\Unit\Service;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Mapper\MasterMapper;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;
use GibsonOS\Module\Hc\Service\ReceiverService;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\UnitTest\AbstractTest;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class ReceiverServiceTest extends AbstractTest
{
    use ProphecyTrait;

    /**
     * @var TransformService
     */
    private $transformService;

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
        $this->transformService = new TransformService();
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

    /**
     * @dataProvider getReceiveData
     */
    public function testReceive(?string $data, ?string $cleanData, int $type, bool $newMaster = false): void
    {
        $protocol = $this->prophesize(ProtocolInterface::class);
        $protocol->receive()
            ->shouldBeCalledOnce()
            ->willReturn($data)
        ;

        if (!empty($data)) {
            $this->masterMapper->checksumEqual($data)
                ->shouldBeCalledOnce()
            ;
            $this->masterMapper->getMasterAddress($data)
                ->shouldBeCalledOnce()
                ->willReturn(42)
            ;
            $this->masterMapper->getType($data)
                ->shouldBeCalledOnce()
                ->willReturn($type)
            ;
            $this->masterMapper->getData($data)
                ->shouldBeCalledOnce()
                ->willReturn($cleanData)
            ;
            $protocol->sendReceiveReturn(42)
                ->shouldBeCalledOnce()
            ;
            $protocol->getName()
                ->shouldBeCalledOnce()
                ->willReturn('prefect')
            ;

            $master = $this->prophesize(Master::class);

            if ($type === 1) {
                $master->getAddress()
                    ->shouldBeCalledOnce()
                    ->willReturn(24)
                ;
                $master->setAddress(42)
                    ->shouldBeCalledOnce()
                ;
                $getByNameCall = $this->masterRepository->getByName($cleanData, 'prefect')
                    ->shouldBeCalledOnce()
                ;

                if ($newMaster) {
                    $getByNameCall->willThrow(SelectError::class);
                    $this->masterRepository->add($cleanData, 'prefect')
                        ->shouldBeCalledOnce()
                        ->willReturn($master->reveal())
                    ;
                } else {
                    $getByNameCall->willReturn($master->reveal());
                }
            } else {
                $this->masterRepository->getByAddress(42, 'prefect')
                    ->shouldBeCalledOnce()
                    ->willReturn($master->reveal())
                ;
                $this->masterService->receive($master->reveal(), $type, $cleanData)
                    ->shouldBeCalledOnce()
                ;
            }
        }

        $this->receiverService->receive($protocol->reveal());
    }

    public function getReceiveData(): array
    {
        return [
            'Data null' => [null, null, 255],
            'Data empty' => ['', '',  255],
            'Handshake' => ['Herz aus Gold', 'Unwarscheinlich', 1],
            'Handshake new' => ['Herz aus Gold', 'Unwarscheinlich', 1, true],
            'Receive' => ['Herz aus Gold', 'Unwarscheinlich', 255],
        ];
    }
}
