<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Module\Hc\Controller\HcSlaveController;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class HcSlaveControllerTest extends HcFunctionalTest
{
    private HcSlaveController $hcSlaveController;

    protected function _before(): void
    {
        parent::_before();

        $this->hcSlaveController = $this->serviceManager->get(HcSlaveController::class);
    }

    public function testGetEepromSettings(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );
        $this->prophesizeRead($module, 213, 2, chr(21));
        $this->prophesizeRead($module, 214, 2, chr(84));

        $this->checkSuccessResponse(
            $this->hcSlaveController->getEepromSettings(
                $this->serviceManager->get(ModuleFactory::class),
                $module,
            ),
            [
                'size' => null,
                'free' => 21,
                'position' => 84,
            ],
        );
    }

    public function testPostEepromSettings(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );
        $this->prophesizeWrite($module, 214, chr(0) . chr(84));

        $this->checkSuccessResponse(
            $this->hcSlaveController->postEepromSettings(
                $this->serviceManager->get(ModuleFactory::class),
                $module,
                84,
            )
        );
    }

    public function testDeleteEeprom(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );
        $this->prophesizeWrite(
            $module,
            215,
            chr($module->getDeviceId() >> 8) . chr($module->getDeviceId() & 255),
        );

        $this->checkSuccessResponse(
            $this->hcSlaveController->deleteEeprom(
                $this->serviceManager->get(ModuleFactory::class),
                $module,
            )
        );
    }

    public function testPostRestart(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );
        $this->prophesizeWrite(
            $module,
            209,
            chr($module->getDeviceId() >> 8) . chr($module->getDeviceId() & 255),
        );

        $this->checkSuccessResponse(
            $this->hcSlaveController->postRestart(
                $this->serviceManager->get(ModuleFactory::class),
                $module,
            )
        );
    }

    public function testGetStatusLedsNoLeds(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );
        $this->prophesizeRead($module, 220, 1, chr(0));

        $this->checkSuccessResponse(
            $this->hcSlaveController->getStatusLeds(
                $this->serviceManager->get(ModuleFactory::class),
                $module,
            ),
            [
                'exist' => [
                    'power' => false,
                    'error' => false,
                    'connect' => false,
                    'transreceive' => false,
                    'transceive' => false,
                    'receive' => false,
                    'custom' => false,
                    'rgb' => false,
                ],
            ],
        );
    }

    public function testGetStatusLeds(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );
        $this->prophesizeRead($module, 220, 1, chr(255));
        $this->prophesizeRead($module, 229, 1, chr(255));
        $this->prophesizeRead(
            $module,
            228,
            9,
            chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255),
        );

        $this->checkSuccessResponse(
            $this->hcSlaveController->getStatusLeds(
                $this->serviceManager->get(ModuleFactory::class),
                $module,
            ),
            [
                'exist' => [
                    'power' => true,
                    'error' => true,
                    'connect' => true,
                    'transreceive' => true,
                    'transceive' => true,
                    'receive' => true,
                    'custom' => true,
                    'rgb' => true,
                ],
                'power' => true,
                'error' => true,
                'connect' => true,
                'transreceive' => true,
                'transceive' => true,
                'receive' => true,
                'custom' => true,
                'powerCode' => 'fff',
                'errorCode' => 'fff',
                'connectCode' => 'fff',
                'transceiveCode' => 'fff',
                'receiveCode' => 'fff',
                'customCode' => 'fff',
            ],
        );
    }

    public function testGetStatusLedsWithoutRgb(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );
        $this->prophesizeRead($module, 220, 1, chr(254));
        $this->prophesizeRead($module, 229, 1, chr(255));

        $this->checkSuccessResponse(
            $this->hcSlaveController->getStatusLeds(
                $this->serviceManager->get(ModuleFactory::class),
                $module,
            ),
            [
                'exist' => [
                    'power' => true,
                    'error' => true,
                    'connect' => true,
                    'transreceive' => true,
                    'transceive' => true,
                    'receive' => true,
                    'custom' => true,
                    'rgb' => false,
                ],
                'power' => true,
                'error' => true,
                'connect' => true,
                'transreceive' => true,
                'transceive' => true,
                'receive' => true,
                'custom' => true,
            ],
        );
    }

    public function testPostStatusLeds(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );
        $this->prophesizeWrite($module, 229, chr(254) . chr(97));
        $this->prophesizeWrite(
            $module,
            228,
            chr(0) . chr(240) . chr(240) . chr(240) . chr(0) . chr(255) . chr(255) . chr(15) . chr(255)
        );

        $this->checkSuccessResponse(
            $this->hcSlaveController->postStatusLeds(
                $this->serviceManager->get(ModuleFactory::class),
                $module,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                '00F',
                '0F0',
                'F00',
                '0FF',
                'FF0',
                'FFF',
            ),
        );
    }
}
