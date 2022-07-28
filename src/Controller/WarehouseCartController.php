<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use Generator;
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
use GibsonOS\Module\Hc\Model\Warehouse\Cart\Item;
use GibsonOS\Module\Hc\Store\Warehouse\Cart\ItemStore;
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
        $cartStore->setLimit($limit, $start);

        return $this->returnSuccess($cartStore->getList(), $cartStore->getCount());
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ)]
    public function items(
        ItemStore $itemStore,
        #[GetModel] ?Cart $cart,
        int $start = 0,
        int $limit = 100
    ): AjaxResponse {
        if ($cart === null) {
            return $this->returnSuccess([], 0);
        }

        $itemStore
            ->setCart($cart)
            ->setLimit($limit, $start)
        ;
        /** @var Generator<Item> $list */
        $list = $itemStore->getList();

        return new AjaxResponse([
            'name' => $cart->getName(),
            'description' => $cart->getDescription(),
            'data' => iterator_to_array($list),
            'total' => $itemStore->getCount(),
            'success' => true,
            'failure' => false,
        ]);
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
