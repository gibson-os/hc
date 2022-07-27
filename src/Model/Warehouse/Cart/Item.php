<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Cart;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item as BoxItem;
use GibsonOS\Module\Hc\Model\Warehouse\Cart;
use JsonSerializable;

/**
 * @method Cart getCart()
 * @method Item setCart(Cart $cart)
 * @method Box  getItem()
 * @method Item setItem(BoxItem $item)
 */
#[Table]
#[Key(true, ['cart_id', 'item_id'])]
class Item extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $stock = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $itemId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $cartId;

    #[Constraint]
    protected BoxItem $item;

    #[Constraint]
    protected Cart $cart;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Item
    {
        $this->id = $id;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): Item
    {
        $this->stock = $stock;

        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): Item
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getCartId(): int
    {
        return $this->cartId;
    }

    public function setCartId(int $cartId): Item
    {
        $this->cartId = $cartId;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'stock' => $this->getStock(),
            'itemId' => $this->getItemId(),
        ];
    }
}
