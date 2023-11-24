<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Module\Hc\Controller\BlankController;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Service\Module\BlankService;
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
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );
        $this->prophesizeRead($module, 97, 7, 'galaxy');

        $response = $this->blankController->get(
            $this->serviceManager->get(BlankService::class),
            $this->serviceManager->get(TransformService::class),
            $module,
            97,
            'hex',
            7,
        );
        $this->checkSuccessResponse($response, '67616c617879');
    }

    public function testPost(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );

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
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('New')
                ->setHelper('blank'),
        );

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
