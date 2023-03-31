<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use JsonSerializable;

#[Table]
class Tag extends AbstractModel implements JsonSerializable, AutoCompleteModelInterface
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    #[Key(true)]
    private string $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Tag
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Tag
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

    public function getAutoCompleteId(): int
    {
        return $this->getId() ?? 0;
    }
}
