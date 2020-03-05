<?php declare(strict_types=1);

namespace Service;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Service\Formatter\MasterFormatter;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;
use GibsonOS\Module\Hc\Service\ReceiverService;
use GibsonOS\Module\Hc\Service\TransformService;
use Prophecy\Prophecy\ObjectProphecy;

class ReceiverServiceTest extends \Codeception\Test\Unit
{
    /**
     * @var ObjectProphecy|TransformService
     */
    private $transformService;

    /**
     * @var ObjectProphecy|MasterService
     */
    private $masterService;

    /**
     * @var ObjectProphecy|MasterFormatter
     */
    private $masterFormatter;

    /**
     * @var ObjectProphecy|MasterRepository
     */
    private $masterRepository;

    /**
     * @var ReceiverService
     */
    private $receiverService;

    protected function _before()
    {
        $this->transformService = $this->prophesize(TransformService::class);
        $this->masterService = $this->prophesize(MasterService::class);
        $this->masterFormatter = $this->prophesize(MasterFormatter::class);
        $this->masterRepository = $this->prophesize(MasterRepository::class);
        $this->receiverService = new ReceiverService(
            $this->transformService->reveal(),
            $this->masterService->reveal(),
            $this->masterFormatter->reveal(),
            $this->masterRepository->reveal()
        );
    }

    /**
     * @dataProvider getReceiveData
     */
    public function testReceive(?string $data, ?string $cleanData, int $type, bool $newMaster = false)
    {
        $protocol = $this->prophesize(ProtocolInterface::class);
        $protocol->receive()
            ->shouldBeCalledOnce()
            ->willReturn($data)
        ;

        if (!empty($data)) {
            $this->masterFormatter->checksumEqual($data)
                ->shouldBeCalledOnce()
            ;
            $this->masterFormatter->getMasterAddress($data)
                ->shouldBeCalledOnce()
                ->willReturn(42)
            ;
            $this->masterFormatter->getType($data)
                ->shouldBeCalledOnce()
                ->willReturn($type)
            ;
            $this->masterFormatter->getData($data)
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
