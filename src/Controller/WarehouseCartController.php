<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModel;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Model\Warehouse\Cart;
use GibsonOS\Module\Hc\Store\Warehouse\CartStore;
use JsonException;
use ReflectionException;

class WarehouseCartController extends AbstractController
{
    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ)]
    public function index(CartStore $cartStore, int $start = 0, int $limit = 100): AjaxResponse
    {
        $cartStore->setLimit($start, $limit);

        return $this->returnSuccess($cartStore->getList(), $cartStore->getCount());
    }

    #[CheckPermission(Permission::READ)]
    public function items(#[GetModel] ?Cart $cart): AjaxResponse
    {
        return $this->returnSuccess($cart?->getItems() ?? []);
    }

    /**
     * @throws SaveError
     * @throws JsonException
     * @throws ReflectionException
     */
    #[CheckPermission(Permission::WRITE)]
    public function save(
        ModelManager $modelManager,
        #[GetMappedModel] Cart $cart
    ): AjaxResponse {
        $modelManager->save($cart);

        return $this->returnSuccess();
    }
}
