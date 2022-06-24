<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Box;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Dto\Warehouse\Code as CodeType;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use JsonSerializable;

/**
 * @method Link setBox(Box $box)
 * @method Box  getBox()
 */
#[Table]
#[Key(unique: true, columns: ['box_id', 'type', 'code'])]
class Code extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $boxId;

    #[Column]
    private CodeType $type;

    #[Column(type: Column::TYPE_VARCHAR, length: 128)]
    private string $code;

    #[Constraint]
    protected Box $box;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Code
    {
        $this->id = $id;

        return $this;
    }

    public function getBoxId(): int
    {
        return $this->boxId;
    }

    public function setBoxId(int $boxId): Code
    {
        $this->boxId = $boxId;

        return $this;
    }

    public function getType(): CodeType
    {
        return $this->type;
    }

    public function setType(CodeType $type): Code
    {
        $this->type = $type;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): Code
    {
        $this->code = $code;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getType()->value,
            'code' => $this->getCode(),
        ];
    }
}