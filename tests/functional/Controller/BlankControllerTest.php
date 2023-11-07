<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Module\Hc\Controller\BlankController;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\Module\BlankService;
use GibsonOS\Module\Hc\Service\Protocol\UdpService;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class BlankControllerTest extends HcFunctionalTest
{
    private BlankController $blankController;

    protected function _before(): void
    {
        parent::_before();

        $this->blankController = $this->serviceManager->get(BlankController::class);
    }

    public function testGet(): void
    {
        $udpService = $this->prophesize(UdpService::class);
        $this->serviceManager->setService(UdpService::class, $udpService->reveal());
        $receiveBusMessage = (new BusMessage('42.42.42.42', 255))
            ->setChecksum(45)
            ->setData('galaxy')
        ;
        $udpService->receiveReadData(42)
            ->shouldBeCalledOnce()
            ->willReturn($receiveBusMessage)
        ;
        $busMessage = (new BusMessage('42.42.42.42', 255))
            ->setCommand(97)
            ->setPort(42)
            ->setSlaveAddress(103)
            ->setData(chr(7))
        ;
        $udpService->send($busMessage)
            ->shouldBeCalledOnce()
        ;

        $master = (new Master($this->modelWrapper))
            ->setAddress('42.42.42.42')
            ->setSendPort(42)
            ->setProtocol('udp')
        ;
        $module = (new Module($this->modelWrapper))
            ->setMaster($master)
            ->setAddress(103)
        ;

        $response = $this->blankController->get(
            $this->serviceManager->get(BlankService::class),
            $this->serviceManager->get(TransformService::class),
            $module,
            97,
            'hex',
            7,
        );
        $this->checkSuccessResponse($response, '6c617879');
    }

    public function testPost(): void
    {
        $udpService = $this->prophesize(UdpService::class);
        $this->serviceManager->setService(UdpService::class, $udpService->reveal());

        $master = (new Master($this->modelWrapper))
            ->setAddress('42.42.42.42')
            ->setSendPort(42)
            ->setProtocol('udp')
        ;
        $module = (new Module($this->modelWrapper))
            ->setMaster($master)
            ->setAddress(103)
        ;

        $response = $this->blankController->post(
            $this->serviceManager->get(BlankService::class),
            $this->serviceManager->get(TransformService::class),
            $module,
            97,
            'hex',
            '1A',
            false,
        );
        $this->checkSuccessResponse($response, '00011010');
    }

    public function testPostHcData(): void
    {
        $udpService = $this->prophesize(UdpService::class);
        $this->serviceManager->setService(UdpService::class, $udpService->reveal());

        $master = (new Master($this->modelWrapper))
            ->setAddress('42.42.42.42')
            ->setSendPort(42)
            ->setProtocol('udp')
        ;
        $module = (new Module($this->modelWrapper))
            ->setMaster($master)
            ->setAddress(103)
        ;

        $response = $this->blankController->post(
            $this->serviceManager->get(BlankService::class),
            $this->serviceManager->get(TransformService::class),
            $module,
            97,
            'hex',
            '1A',
            true,
        );
        $this->checkSuccessResponse($response, '00011010');
    }
}
