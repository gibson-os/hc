<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Box\Item;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item;

/**
 * @method Link setItem(Item $item)
 * @method Item getItem()
 */
#[Table]
#[Key(unique: true, columns: ['item_id', 'name'])]
#[Key(unique: true, columns: ['item_id', 'url'])]
class Link extends AbstractModel implements \JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $itemId;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    private string $name;

    #[Column(type: Column::TYPE_VARCHAR, length: 256)]
    private string $url;

    #[Constraint]
    protected Item $item;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Link
    {
        $this->id = $id;

        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): Link
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Link
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): Link
    {
        $this->url = $url;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'url' => $this->getUrl(),
        ];
    }
}
