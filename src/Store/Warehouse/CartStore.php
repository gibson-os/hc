<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Warehouse;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Warehouse\Cart;

/**
 * @extends AbstractDatabaseStore<Cart>
 */
class CartStore extends AbstractDatabaseStore
{
    protected function getModelClassName(): string
    {
        return Cart::class;
    }
}
