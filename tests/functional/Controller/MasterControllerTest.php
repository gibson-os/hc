<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Module\Hc\Controller\MasterController;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Store\MasterStore;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class MasterControllerTest extends HcFunctionalTest
{
    private MasterController $masterController;

    protected function _before(): void
    {
        parent::_before();

        $this->masterController = $this->serviceManager->get(MasterController::class);
    }

    public function testGet(): void
    {
        $masterArthur = (new Master($this->modelWrapper))
            ->setName('arthur')
            ->setProtocol('udp')
            ->setAddress('42.42.42.1')
            ->setSendPort(42)
        ;
        $masterDent = (new Master($this->modelWrapper))
            ->setName('dent')
            ->setProtocol('udp')
            ->setAddress('42.42.42.2')
            ->setSendPort(420)
        ;
        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $modelManager->saveWithoutChildren($masterDent);
        $modelManager->saveWithoutChildren($masterArthur);

        $response = $this->masterController->get($this->serviceManager->get(MasterStore::class));
        $this->checkSuccessResponse($response, [$masterArthur->jsonSerialize(), $masterDent->jsonSerialize()], 2);
    }
}
