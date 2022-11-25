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
 * @method File setItem(Item $item)
 * @method Item getItem()
 */
#[Table]
#[Key(unique: true, columns: ['item_id', 'name'])]
class File extends AbstractModel implements \JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $itemId;

    #[Column(type: Column::TYPE_VARCHAR, length: 128)]
    private string $name;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    private string $fileName;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    private string $mimeType;

    #[Constraint]
    protected Item $item;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): File
    {
        $this->id = $id;

        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): File
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): File
    {
        $this->name = $name;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): File
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): File
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'fileName' => $this->getFileName(),
            'mimeType' => $this->getMimeType(),
        ];
    }
}
