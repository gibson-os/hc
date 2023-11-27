<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller\HcSlaveController;

use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Module\Hc\Controller\HcSlaveController;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class HcSlaveControllerGeneralSettingsTest extends HcFunctionalTest
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

        $moduleById = $this->serviceManager->get(ModuleRepository::class)->getById($module->getId());
        $moduleById->getMaster();
        $moduleById->getType();

        $this->assertEquals($module, $moduleById);
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

        $moduleById = $this->serviceManager->get(ModuleRepository::class)->getById($module->getId());
        $this->assertEquals('arthur', $moduleById->getName());
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

        $this->assertEquals(
            2424,
            $this->serviceManager->get(ModuleRepository::class)->getById($module->getId())->getDeviceId(),
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
        $this->prophesizeRead($module, 201, 1, chr(255));
        $this->prophesizeWrite($module, 201, chr(7) . chr(97));
        $this->prophesizeRead($module, 201, 1, chr(7));
        $this->prophesizeRead($module, 201, 1, chr(7));
        $this->prophesizeRead($module, 200, 2, chr($module->getDeviceId() >> 8) . chr($module->getDeviceId() & 255));
        $this->prophesizeRead($module, 217, 2, chr(42) . chr(0));
        $this->prophesizeRead($module, 211, 4, chr(42) . chr(0) . chr(42) . chr(0));
        $this->prophesizeRead($module, 216, 2, chr(0) . chr(42));
        $this->prophesizeRead($module, 212, 2, chr(0) . chr(42));

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
                'hertz' => 704653824,
                'typeId' => 7,
                'address' => 42,
                'id' => 7,
                'type' => 'Ford',
                'helper' => 'blank',
                'offline' => false,
                'settings' => [],
                'added' => $module->getAdded()->format('Y-m-d H:i:s'),
                'modified' => $module->getModified()->format('Y-m-d H:i:s'),
            ],
        );

        $modelById = $this->serviceManager->get(ModuleRepository::class)->getById($module->getId());
        $this->assertEquals(7, $modelById->getTypeId());
        $this->assertEquals(10752, $modelById->getPwmSpeed());
        $this->assertEquals(704653824, $modelById->getHertz());
        $this->assertEquals(42, $modelById->getBufferSize());
    }

    public function testPostGeneralSettingsAddress(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );
        $this->prophesizeWrite(
            $module,
            202,
            chr($module->getDeviceId() >> 8) . chr($module->getDeviceId() & 255) . chr(84),
        );
        $this->prophesizeReadMaster($module->getMaster(), type: 5);

        $response = $this->hcSlaveController->postGeneralSettings(
            $this->serviceManager->get(ModuleFactory::class),
            $this->serviceManager->get(ModuleRepository::class),
            $this->serviceManager->get(TypeRepository::class),
            $this->serviceManager->get(ModelManager::class),
            $module,
            $module->getName(),
            $module->getDeviceId(),
            $module->getTypeId(),
            84,
            $module->getPwmSpeed(),
        );

        $this->checkSuccessResponse(
            $response,
            [
                'name' => 'marvin',
                'deviceId' => 4242,
                'hertz' => 0,
                'typeId' => 255,
                'address' => 84,
                'id' => 7,
                'type' => 'New',
                'helper' => 'blank',
                'offline' => false,
                'settings' => [],
                'added' => $module->getAdded()->format('Y-m-d H:i:s'),
                'modified' => $module->getModified()->format('Y-m-d H:i:s'),
            ],
        );

        $modelById = $this->serviceManager->get(ModuleRepository::class)->getById($module->getId());
        $this->assertEquals(84, $modelById->getAddress());
    }

    public function testPostGeneralSettingsPwmSpeed(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );
        $this->prophesizeWrite(
            $module,
            217,
            chr(0) . chr(210),
        );

        $response = $this->hcSlaveController->postGeneralSettings(
            $this->serviceManager->get(ModuleFactory::class),
            $this->serviceManager->get(ModuleRepository::class),
            $this->serviceManager->get(TypeRepository::class),
            $this->serviceManager->get(ModelManager::class),
            $module,
            $module->getName(),
            $module->getDeviceId(),
            $module->getTypeId(),
            $module->getAddress(),
            210,
        );

        $this->checkSuccessResponse(
            $response,
            [
                'name' => 'marvin',
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

        $modelById = $this->serviceManager->get(ModuleRepository::class)->getById($module->getId());
        $this->assertEquals(210, $modelById->getPwmSpeed());
    }
}
