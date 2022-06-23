<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Warehouse\Cart\Item;
use JsonSerializable;

/**
 * @method Item[] getItems()
 * @method Cart   setItems(Item[] $items)
 * @method Cart   addItems(Item[] $items)
 */
#[Table]
class Cart extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    #[Key(true)]
    private string $name;

    #[Constraint('box', Item::class)]
    protected array $items = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Cart
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Cart
    {
        $this->name = $name;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
        ];
    }
}
