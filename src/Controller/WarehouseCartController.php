<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Store\Warehouse\CartStore;

class WarehouseCartController extends AbstractController
{
    #[CheckPermission(Permission::READ)]
    public function index(CartStore $cartStore, int $start = 0, int $limit = 100): AjaxResponse
    {
        $cartStore->setLimit($start, $limit);

        return $this->returnSuccess($cartStore->getList(), $cartStore->getCount());
    }
}
