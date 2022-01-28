<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Slave\IrService;
use GibsonOS\Module\Hc\Store\Ir\KeyStore;

class IrController extends AbstractController
{
    /**
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ)]
    public function keys(KeyStore $keyStore): AjaxResponse
    {
        return $this->returnSuccess(
            $keyStore->getList(),
            $keyStore->getCount()
        );
    }

    #[CheckPermission(Permission::MANAGE + Permission::WRITE)]
    public function addKey(): AjaxResponse
    {
        return $this->returnSuccess();
    }

    #[CheckPermission(Permission::READ)]
    public function waitForKey(): AjaxResponse
    {
        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function send(
        ModuleRepository $moduleRepository,
        IrService $irService,
        int $moduleId,
        int $protocol,
        int $address,
        int $command
    ): AjaxResponse {
        $module = $moduleRepository->getById($moduleId);

        $irService->sendKey($module, new Key($protocol, $address, $command));

        return $this->returnSuccess();
    }
}
