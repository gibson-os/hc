<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Box;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use JsonSerializable;

/**
 * @method Link setBox(Box $box)
 * @method Box  getBox()
 */
#[Table]
#[Key(unique: true, columns: ['box_id', 'url'])]
class Link extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $boxId;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    private string $name;

    #[Column(type: Column::TYPE_VARCHAR, length: 256)]
    private string $url;

    #[Constraint]
    protected Box $box;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Link
    {
        $this->id = $id;

        return $this;
    }

    public function getBoxId(): int
    {
        return $this->boxId;
    }

    public function setBoxId(int $boxId): Link
    {
        $this->boxId = $boxId;

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
