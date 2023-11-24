<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Module\Hc\Controller\HcSlaveController;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class HcSlaveControllerTest extends HcFunctionalTest
{
    private HcSlaveController $hcSlaveController;

    protected function _before(): void
    {
        parent::_before();

        $this->hcSlaveController = $this->serviceManager->get(HcSlaveController::class);
    }

    public function testGetGeneralSettings(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );

        $this->checkSuccessResponse(
            $this->hcSlaveController->getGeneralSettings($module),
            [
                'name' => 'marvin',
                'hertz' => null,
                'bufferSize' => null,
                'deviceId' => 4242,
                'typeId' => 255,
                'address' => 42,
                'pwmSpeed' => null,
            ],
        );
    }

    public function testPostGeneralSettingsName(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );

        $response = $this->hcSlaveController->postGeneralSettings(
            $this->serviceManager->get(ModuleFactory::class),
            $this->serviceManager->get(ModuleRepository::class),
            $this->serviceManager->get(TypeRepository::class),
            $this->serviceManager->get(ModelManager::class),
            $module,
            'arthur',
            $module->getDeviceId(),
            $module->getTypeId(),
            $module->getAddress(),
            $module->getPwmSpeed(),
        );

        $this->checkSuccessResponse(
            $response,
            [
                'name' => 'arthur',
                'deviceId' => 4242,
                'hertz' => 0,
                'typeId' => 255,
                'address' => 42,
                'id' => 7,
                'type' => 'New',
                'helper' => 'blank',
                'offline' => false,
                'settings' => [],
                'added' => $module->getAdded()->format('Y-m-d H:i:s'),
                'modified' => $module->getModified()->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function testPostGeneralSettingsDeviceId(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );

        $response = $this->hcSlaveController->postGeneralSettings(
            $this->serviceManager->get(ModuleFactory::class),
            $this->serviceManager->get(ModuleRepository::class),
            $this->serviceManager->get(TypeRepository::class),
            $this->serviceManager->get(ModelManager::class),
            $module,
            $module->getName(),
            2424,
            $module->getTypeId(),
            $module->getAddress(),
            $module->getPwmSpeed(),
        );

        $this->checkSuccessResponse(
            $response,
            [
                'name' => 'marvin',
                'deviceId' => 2424,
                'hertz' => 0,
                'typeId' => 255,
                'address' => 42,
                'id' => 7,
                'type' => 'New',
                'helper' => 'blank',
                'offline' => false,
                'settings' => [],
                'added' => $module->getAdded()->format('Y-m-d H:i:s'),
                'modified' => $module->getModified()->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function testPostGeneralSettingsTypeId(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );
        $this->serviceManager->get(ModelManager::class)->saveWithoutChildren(
            (new Type($this->modelWrapper))
                ->setId(7)
                ->setName('Ford')
                ->setHelper('blank')
        );
        $this->prophesizeRead($module, 201, 1, chr($module->getTypeId()));
        $this->prophesizeWrite($module, 201, chr(7));

        $response = $this->hcSlaveController->postGeneralSettings(
            $this->serviceManager->get(ModuleFactory::class),
            $this->serviceManager->get(ModuleRepository::class),
            $this->serviceManager->get(TypeRepository::class),
            $this->serviceManager->get(ModelManager::class),
            $module,
            $module->getName(),
            $module->getDeviceId(),
            7,
            $module->getAddress(),
            $module->getPwmSpeed(),
        );

        $this->checkSuccessResponse(
            $response,
            [
                'name' => 'marvin',
                'deviceId' => 4242,
                'hertz' => 0,
                'typeId' => 7,
                'address' => 42,
                'id' => 7,
                'type' => 'Ford',
                'helper' => 'prefect',
                'offline' => false,
                'settings' => [],
                'added' => $module->getAdded()->format('Y-m-d H:i:s'),
                'modified' => $module->getModified()->format('Y-m-d H:i:s'),
            ],
        );
    }
}
