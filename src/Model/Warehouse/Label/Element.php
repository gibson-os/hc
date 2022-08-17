<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Label;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Dto\Warehouse\LabelType;
use GibsonOS\Module\Hc\Model\Warehouse\Label;

/**
 * @method Label   getLabel()
 * @method Element setLabel(Label $label)
 */
#[Table]
class Element extends AbstractModel
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $top;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $left;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $width;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $height;

    #[Column(type: Column::TYPE_VARCHAR, length: 6)]
    private string $color;

    #[Column(type: Column::TYPE_VARCHAR, length: 6)]
    private string $backgroundColor;

    #[Column]
    private LabelType $type;

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

    public function getTop(): int
    {
        return $this->top;
    }

    public function setTop(int $top): Element
    {
        $this->top = $top;

        return $this;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function setLeft(int $left): Element
    {
        $this->left = $left;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): Element
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): Element
    {
        $this->height = $height;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): Element
    {
        $this->color = $color;

        return $this;
    }

    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(string $backgroundColor): Element
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    public function getType(): LabelType
    {
        return $this->type;
    }

    public function setType(LabelType $type): Element
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
}
