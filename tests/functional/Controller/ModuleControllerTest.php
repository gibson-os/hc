<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Module\Hc\Controller\ModuleController;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Store\ModuleStore;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class ModuleControllerTest extends HcFunctionalTest
{
    private ModuleController $moduleController;

    protected function _before(): void
    {
        parent::_before();

        $this->moduleController = $this->serviceManager->get(ModuleController::class);
    }

    public function testPost(): void
    {

    }

    public function testGet(): void
    {
        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $type = (new Type($this->modelWrapper))
            ->setId(42)
            ->setName('marvin')
            ->setHelper('trillian')
        ;
        $modelManager->saveWithoutChildren($type);
        $moduleArthur = (new Module($this->modelWrapper))
            ->setName('arthur')
            ->setAddress(42)
            ->setType($type)
        ;
        $moduleDent = (new Module($this->modelWrapper))
            ->setName('dent')
            ->setAddress(84)
            ->setType($type)
        ;
        $modelManager->saveWithoutChildren($moduleDent);
        $modelManager->saveWithoutChildren($moduleArthur);

        $response = $this->moduleController->get($this->serviceManager->get(ModuleStore::class));
        $this->checkSuccessResponse($response, [$moduleArthur->jsonSerialize(), $moduleDent->jsonSerialize()], 2);
    }

    public function testDelete(): void
    {
        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $type = (new Type($this->modelWrapper))
            ->setId(42)
            ->setName('marvin')
            ->setHelper('trillian')
        ;
        $modelManager->saveWithoutChildren($type);
        $moduleArthur = (new Module($this->modelWrapper))
            ->setName('arthur')
            ->setAddress(42)
            ->setType($type)
        ;
        $moduleDent = (new Module($this->modelWrapper))
            ->setName('dent')
            ->setAddress(84)
            ->setType($type)
        ;
        $modelManager->saveWithoutChildren($moduleDent);
        $modelManager->saveWithoutChildren($moduleArthur);

        $moduleRepository = $this->serviceManager->get(ModuleRepository::class);
        $this->checkSuccessResponse(
            $this->moduleController->delete($moduleRepository, [$moduleArthur->getId()]),
        );

        $this->assertEquals(
            $moduleDent->jsonSerialize(),
            $moduleRepository->getById($moduleDent->getId())->jsonSerialize(),
        );

        $this->expectException(SelectError::class);
        $this->assertEquals($moduleDent, $moduleRepository->getById($moduleArthur->getId()));
    }
}
