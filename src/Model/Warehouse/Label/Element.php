<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Label;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Dto\Warehouse\Label\Element\Type;
use GibsonOS\Module\Hc\Model\Warehouse\Label;
use JsonSerializable;

/**
 * @method Label   getLabel()
 * @method Element setLabel(Label $label)
 */
#[Table]
class Element extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private float $top;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private float $left;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private float $width;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private float $height;

    #[Column(type: Column::TYPE_VARCHAR, length: 6)]
    private ?string $color = null;

    #[Column(type: Column::TYPE_VARCHAR, length: 6)]
    private ?string $backgroundColor = null;

    #[Column]
    private Type $type;

    #[Column]
    private array $options = [];

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $labelId;

    #[Constraint]
    protected Label $label;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Element
    {
        $this->id = $id;

        return $this;
    }

    public function getTop(): float
    {
        return $this->top;
    }

    public function setTop(float $top): Element
    {
        $this->top = $top;

        return $this;
    }

    public function getLeft(): float
    {
        return $this->left;
    }

    public function setLeft(float $left): Element
    {
        $this->left = $left;

        return $this;
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function setWidth(float $width): Element
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function setHeight(float $height): Element
    {
        $this->height = $height;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): Element
    {
        $this->color = $color;

        return $this;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor): Element
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): Element
    {
        $this->type = $type;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): Element
    {
        $this->options = $options;

        return $this;
    }

    public function getLabelId(): int
    {
        return $this->labelId;
    }

    public function setLabelId(int $labelId): Element
    {
        $this->labelId = $labelId;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'top' => $this->getTop(),
            'left' => $this->getLeft(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'color' => $this->getColor(),
            'backgroundColor' => $this->getBackgroundColor(),
            'type' => $this->getType()->name,
            'options' => $this->getOptions(),
        ];
    }
}
