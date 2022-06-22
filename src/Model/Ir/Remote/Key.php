<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Ir\Remote;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key as KeyAttribute;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Ir\Key as IrKey;
use JsonSerializable;

/**
 * @method Button getButton()
 * @method Key    setButton(Button $button)
 * @method IrKey  getKey()
 * @method Key    setKey(IrKey $key)
 */
#[Table]
#[KeyAttribute(unique: true, columns: ['button_id', 'order'])]
class Key extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $buttonId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $keyId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $order = 0;

    #[Constraint]
    protected Button $button;

    #[Constraint]
    protected IrKey $key;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Key
    {
        $this->id = $id;

        return $this;
    }

    public function getButtonId(): int
    {
        return $this->buttonId;
    }

    public function setButtonId(int $buttonId): Key
    {
        $this->buttonId = $buttonId;

        return $this;
    }

    public function getKeyId(): int
    {
        return $this->keyId;
    }

    public function setKeyId(int $keyId): Key
    {
        $this->keyId = $keyId;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): Key
    {
        $this->order = $order;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'order' => $this->getOrder(),
            'key' => $this->getKey(),
        ];
    }
}
