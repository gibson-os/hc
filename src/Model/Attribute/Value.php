<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Attribute;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Model\Attribute;
use JsonSerializable;

/**
 * @method Attribute getAttribute()
 * @method Value     setAttribute(Attribute $attribute)
 */
#[Table]
class Value extends AbstractModel implements AutoCompleteModelInterface, JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], primary: true)]
    private int $attributeId;

    #[Column(type: Column::TYPE_INT, attributes: [Column::ATTRIBUTE_UNSIGNED], primary: true)]
    private int $order = 0;

    #[Column(type: Column::TYPE_TEXT)]
    private string $value;

    #[Constraint]
    protected Attribute $attribute;

    public function getAttributeId(): int
    {
        return $this->attributeId;
    }

    public function setAttributeId(int $attributeId): Value
    {
        $this->attributeId = $attributeId;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): Value
    {
        $this->order = $order;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Value
    {
        $this->value = $value;

        return $this;
    }

    public function getAutoCompleteId(): string
    {
        return $this->getAttributeId() . '_' . $this->getOrder();
    }

    public function jsonSerialize(): array
    {
        return [
            'attributeId' => $this->getAttributeId(),
            'order' => $this->getOrder(),
            'value' => $this->getValue(),
        ];
    }
}
