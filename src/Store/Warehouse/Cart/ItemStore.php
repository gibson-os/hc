<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Warehouse\Cart;

use GibsonOS\Core\Dto\Model\ChildrenMapping;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Warehouse\Cart;
use GibsonOS\Module\Hc\Model\Warehouse\Cart\Item;

/**
 * @extends AbstractDatabaseStore<Item>
 */
class ItemStore extends AbstractDatabaseStore
{
    public function setCart(Cart $cart): ItemStore
    {
        $this->addWhere('`i`.`cart_id`=?', [$cart->getId()]);

        return $this;
    }

    protected function getModelClassName(): string
    {
        return Item::class;
    }

    protected function getAlias(): ?string
    {
        return 'i';
    }

    protected function getExtends(): array
    {
        return [new ChildrenMapping('item', 'bi', 'box_item_')];
    }
}
