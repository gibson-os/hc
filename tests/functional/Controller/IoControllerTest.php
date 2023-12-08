<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\MiddlewareService;
use GibsonOS\Module\Hc\Controller\IoController;
use GibsonOS\Module\Hc\Enum\Io\Direction;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\Io\PortRepository;
use GibsonOS\Module\Hc\Service\Module\IoService;
use GibsonOS\Module\Hc\Store\Io\PortStore;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class IoControllerTest extends HcFunctionalTest
{
    private IoController $ioController;

    protected function _before(): void
    {
        parent::_before();

        $middlewareService = $this->prophesize(MiddlewareService::class);
        $this->serviceManager->setService(MiddlewareService::class, $middlewareService->reveal());

        $this->ioController = $this->serviceManager->get(IoController::class);
    }

    public function testPost(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $port = (new Port($this->modelWrapper))
            ->setModule($module)
            ->setName('marvin')
            ->setNumber(42)
        ;
        $this->prophesizeWrite($module, 42, chr(2) . chr(0));

        $this->checkSuccessResponse(
            $this->ioController->post(
                $this->serviceManager->get(IoService::class),
                $module,
                $port,
            )
        );

        $portById = $this->serviceManager->get(PortRepository::class)->getById($module->getId(), $port->getId());

        $this->assertEquals($port->jsonSerialize(), $portById->jsonSerialize());
    }

    public function testGetPorts(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $modelManager->saveWithoutChildren(
            (new Port($this->modelWrapper))
                ->setModule($module)
                ->setName('Arthur')
                ->setNumber(1)
        );
        $modelManager->saveWithoutChildren(
            (new Port($this->modelWrapper))
                ->setModule($module)
                ->setName('Dent')
                ->setNumber(2)
        );
        $modelManager->saveWithoutChildren(
            (new Port($this->modelWrapper))
                ->setModule($module)
                ->setName('Ford')
                ->setNumber(0)
        );

        $this->checkSuccessResponse(
            $this->ioController->getPorts(
                $this->serviceManager->get(PortStore::class),
                $module,
            ),
            [
                [
                    'id' => 3,
                    'name' => 'Ford',
                    'number' => 0,
                    'direction' => 0,
                    'pullUp' => true,
                    'delay' => 0,
                    'value' => false,
                    'valueNames' => ['Offen', 'Zu'],
                    'pwm' => 0,
                    'blink' => 0,
                    'fadeIn' => 0,
                ], [
                    'id' => 1,
                    'name' => 'Arthur',
                    'number' => 1,
                    'direction' => 0,
                    'pullUp' => true,
                    'delay' => 0,
                    'value' => false,
                    'valueNames' => ['Offen', 'Zu'],
                    'pwm' => 0,
                    'blink' => 0,
                    'fadeIn' => 0,
                ], [
                    'id' => 2,
                    'name' => 'Dent',
                    'number' => 2,
                    'direction' => 0,
                    'pullUp' => true,
                    'delay' => 0,
                    'value' => false,
                    'valueNames' => ['Offen', 'Zu'],
                    'pwm' => 0,
                    'blink' => 0,
                    'fadeIn' => 0,
                ],
            ],
        );
    }

    public function testPostToggleOn(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $port = (new Port($this->modelWrapper))
            ->setModule($module)
            ->setDirection(Direction::OUTPUT)
            ->setName('marvin')
            ->setNumber(42)
            ->setValue(false)
        ;
        $this->prophesizeWrite($module, 42, chr(7) . chr(0));

        $this->checkSuccessResponse(
            $this->ioController->postToggle(
                $this->serviceManager->get(IoService::class),
                $module,
                $port
            )
        );

        $portById = $this->serviceManager->get(PortRepository::class)->getById($module->getId(), $port->getId());

        $this->assertTrue($portById->isValue());
    }

    public function testPostToggleOff(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $port = (new Port($this->modelWrapper))
            ->setModule($module)
            ->setDirection(Direction::OUTPUT)
            ->setName('marvin')
            ->setNumber(42)
            ->setValue(true)
        ;
        $this->prophesizeWrite($module, 42, chr(3) . chr(0));

        $this->checkSuccessResponse(
            $this->ioController->postToggle(
                $this->serviceManager->get(IoService::class),
                $module,
                $port
            )
        );

        $portById = $this->serviceManager->get(PortRepository::class)->getById($module->getId(), $port->getId());

        $this->assertFalse($portById->isValue());
    }

    public function testGetEeeprom(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $this->prophesizeRead($module, 135, 1, chr(255));

        $this->checkSuccessResponse(
            $this->ioController->getEeprom(
                $this->serviceManager->get(IoService::class),
                $this->serviceManager->get(PortRepository::class),
                $module,
            ),
            [],
        );
    }

    public function testGetEeepromEmpty(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $this->prophesizeRead($module, 135, 1);

        $this->expectException(ReceiveError::class);

        $this->ioController->getEeprom(
            $this->serviceManager->get(IoService::class),
            $this->serviceManager->get(PortRepository::class),
            $module,
        );
    }

    public function testPostEeprom(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $this->prophesizeWrite($module, 135, chr(16) . chr(146));

        $this->checkSuccessResponse(
            $this->ioController->postEeprom(
                $this->serviceManager->get(IoService::class),
                $module,
            ),
        );
    }
}
