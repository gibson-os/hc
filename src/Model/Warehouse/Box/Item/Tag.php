<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Box\Item;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item;
use GibsonOS\Module\Hc\Model\Warehouse\Tag as WarehouseTag;
use JsonSerializable;

/**
 * @method Tag          setItem(Item $item)
 * @method Item         getItem()
 * @method Tag          setTag(WarehouseTag $tag)
 * @method WarehouseTag getTag()
 */
#[Table]
#[Key(unique: true, columns: ['item_id', 'tag_id'])]
class Tag extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $itemId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $tagId;

    #[Constraint]
    protected Item $item;

    #[Constraint]
    protected WarehouseTag $tag;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Tag
    {
        $this->id = $id;

        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): Tag
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getTagId(): int
    {
        return $this->tagId;
    }

    public function setTagId(int $tagId): Tag
    {
        $this->tagId = $tagId;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'tag' => $this->getTag(),
        ];
    }
}
