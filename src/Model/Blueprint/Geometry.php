<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Blueprint;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Enum\Blueprint\Geometry as GeometryType;
use GibsonOS\Module\Hc\Model\Blueprint;
use GibsonOS\Module\Hc\Model\Module;

/**
 * @method Blueprint   getBlueprint()
 * @method Geometry    setBlueprint(Blueprint $blueprint)
 * @method Module|null getModule()
 * @method Geometry    setModule(Module|null $module)
 */
#[Table]
class Geometry extends AbstractModel
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $blueprintId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $moduleId = null;

    #[Column]
    private GeometryType $type = GeometryType::LINE;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $top;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $left;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $width = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $height = 0;

    #[Column]
    private array $moduleOptions = [];

    #[Constraint]
    protected Blueprint $blueprint;

    #[Constraint]
    protected ?Module $module = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Geometry
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): GeometryType
    {
        return $this->type;
    }

    public function setType(GeometryType $type): Geometry
    {
        $this->type = $type;

        return $this;
    }

    public function getBlueprintId(): int
    {
        return $this->blueprintId;
    }

    public function setBlueprintId(int $blueprintId): Geometry
    {
        $this->blueprintId = $blueprintId;

        return $this;
    }

    public function getModuleId(): ?int
    {
        return $this->moduleId;
    }

    public function setModuleId(?int $moduleId): Geometry
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getTop(): int
    {
        return $this->top;
    }

    public function setTop(int $top): Geometry
    {
        $this->top = $top;

        return $this;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function setLeft(int $left): Geometry
    {
        $this->left = $left;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): Geometry
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): Geometry
    {
        $this->height = $height;

        return $this;
    }

    public function getModuleOptions(): array
    {
        return $this->moduleOptions;
    }

    public function setModuleOptions(array $moduleOptions): Geometry
    {
        $this->moduleOptions = $moduleOptions;

        return $this;
    }
}
