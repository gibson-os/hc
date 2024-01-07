<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use Generator;
use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModel;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Attribute\GetModels;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Model\Warehouse\Cart;
use GibsonOS\Module\Hc\Model\Warehouse\Cart\Item;
use GibsonOS\Module\Hc\Service\Warehouse\BoxService;
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
    #[CheckPermission([Permission::READ])]
    public function get(CartStore $cartStore, int $start = 0, int $limit = 100): AjaxResponse
    {
        $cartStore->setLimit($limit, $start);

        return $this->returnSuccess($cartStore->getList(), $cartStore->getCount());
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     */
    #[CheckPermission([Permission::READ])]
    public function getItems(
        ItemStore $itemStore,
        #[GetModel]
        ?Cart $cart,
        int $start = 0,
        int $limit = 100,
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
    #[CheckPermission([Permission::WRITE])]
    public function post(
        ModelManager $modelManager,
        #[GetMappedModel]
        Cart $cart,
    ): AjaxResponse {
        $modelManager->save($cart);

        return $this->returnSuccess();
    }

    /**
     * @param Item[] $items
     *
     * @throws SaveError
     * @throws AbstractException
     * @throws DateTimeError
     */
    #[CheckPermission([Permission::WRITE])]
    public function postShow(
        BoxService $boxService,
        #[GetModels(Item::class)]
        array $items,
        int $red = 255,
        int $green = 255,
        int $blue = 255,
        int $fadeIn = 0,
        int $blink = 0,
    ): AjaxResponse {
        $boxService->showLeds(
            array_map(
                fn (Item $item): Box => $item->getItem()->getBox(),
                $items,
            ),
            $red,
            $green,
            $blue,
            $fadeIn,
            $blink,
        );

        return $this->returnSuccess();
    }
}
