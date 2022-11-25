<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Warehouse\Cart;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item as BoxItem;
use GibsonOS\Module\Hc\Model\Warehouse\Cart;
use GibsonOS\Module\Hc\Model\Warehouse\Cart\Item;

class ItemStore extends AbstractDatabaseStore
{
    public function __construct(
        \mysqlDatabase $database = null,
        #[GetTableName(Item::class)] private string $itemTableName,
        #[GetTableName(BoxItem::class)] private string $boxItemTableName,
    ) {
        parent::__construct($database);
    }

    public function setCart(Cart $cart): ItemStore
    {
        $this->addWhere(sprintf('`%s`.`cart_id`=?', $this->itemTableName), [$cart->getId()]);

        return $this;
    }

    protected function getModelClassName(): string
    {
        return Item::class;
    }

    protected function initTable(): void
    {
        parent::initTable();

        $this->table
            ->appendJoinLeft(
                $this->boxItemTableName,
                sprintf('`%s`.`item_id`=`%s`.`id`', $this->itemTableName, $this->boxItemTableName)
            )
            ->setSelectString(sprintf(
                '`%s`.`id`, ' .
                '`%s`.`stock`, ' .
                '`%s`.`item_id`, ' .
                '`%s`.`cart_id`, ' .
                '`%s`.`id` `box_item_id`, ' .
                '`%s`.`box_id` `box_item_box_id`, ' .
                '`%s`.`name` `box_item_name`, ' .
                '`%s`.`image` `box_item_image`, ' .
                '`%s`.`image_mime_type` `box_item_image_mime_type`, ' .
                '`%s`.`stock` `box_item_stock`, ' .
                '`%s`.`description` `box_item_description`',
                $this->itemTableName,
                $this->itemTableName,
                $this->itemTableName,
                $this->itemTableName,
                $this->boxItemTableName,
                $this->boxItemTableName,
                $this->boxItemTableName,
                $this->boxItemTableName,
                $this->boxItemTableName,
                $this->boxItemTableName,
                $this->boxItemTableName,
            ))
        ;
    }

    protected function getModel(): AbstractModel
    {
        $record = $this->table->getSelectedRecord();
        /** @var Item $model */
        $model = parent::getModel();

        return $model->setItem(
            (new BoxItem())
                ->setId((int) $record['box_item_id'])
                ->setBoxId((int) $record['box_item_box_id'])
                ->setName($record['box_item_name'])
                ->setImage($record['box_item_image'])
                ->setImageMimeType($record['box_item_image_mime_type'])
                ->setStock((int) $record['box_item_stock'])
                ->setDescription($record['box_item_description'])
        );
    }
}
