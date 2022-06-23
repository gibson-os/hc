<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Box;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Model\Warehouse\Tag as WarehouseTag;
use JsonSerializable;

/**
 * @method Tag          setBox(Box $box)
 * @method Box          getBox()
 * @method Tag          setTag(WarehouseTag $tag)
 * @method WarehouseTag getTag()
 */
#[Table]
#[Key(unique: true, columns: ['box_id', 'tag_id'])]
class Tag extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $boxId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $tagId;

    #[Constraint]
    protected Box $box;

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

    public function getBoxId(): int
    {
        return $this->boxId;
    }

    public function setBoxId(int $boxId): Tag
    {
        $this->boxId = $boxId;

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
