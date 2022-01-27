<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Store\Ir\KeyStore;

class IrController extends AbstractController
{
    /**
     * @throws SelectError
     *
     * @return AjaxResponse
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
}
