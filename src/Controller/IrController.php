<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Store\Ir\KeyStore;

class IrController extends AbstractController
{
    /**
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
}
