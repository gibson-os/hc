<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Ir\Key;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key as KeyAttribute;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Ir\Key;
use JsonSerializable;

/**
 * @method Key  getKey()
 * @method Name setKey(Key $key)
 */
#[Table]
class Name extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    #[KeyAttribute(true)]
    private string $name;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $keyId;

    #[Constraint]
    protected Key $key;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Name
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Name
    {
        $this->name = $name;

        return $this;
    }

    public function getKeyId(): int
    {
        return $this->keyId;
    }

    public function setKeyId(int $keyId): Name
    {
        $this->keyId = $keyId;

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
