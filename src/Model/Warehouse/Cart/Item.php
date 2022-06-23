<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Cart;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use JsonSerializable;

/**
 * @method Box  getBox()
 * @method Item setBox(Box $box)
 */
#[Table]
class Item extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $stock = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $boxId;

    #[Constraint]
    protected Box $box;

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

    public function getBoxId(): int
    {
        return $this->boxId;
    }

    public function setBoxId(int $boxId): Item
    {
        $this->boxId = $boxId;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'stock' => $this->getStock(),
            'box' => $this->getBox(),
        ];
    }
}
