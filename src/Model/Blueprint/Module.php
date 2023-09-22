<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Blueprint;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Blueprint;
use GibsonOS\Module\Hc\Model\Module as HcModule;

/**
 * @method Blueprint     getBlueprint()
 * @method Module        setBlueprint(Blueprint $blueprint)
 * @method Geometry|null getGeometry()
 * @method Module        setGeometry(Geometry|null $geometry)
 * @method HcModule      getModule()
 * @method Module        setModule(HcModule $module)
 */
#[Table]
class Module extends AbstractModel
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $blueprintId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $geometryId = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $moduleId;

    #[Column]
    private array $options = [];

    #[Constraint]
    protected Blueprint $blueprint;

    #[Constraint]
    protected ?Geometry $geometry = null;

    #[Constraint]
    protected HcModule $module;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Module
    {
        $this->id = $id;

        return $this;
    }

    public function getBlueprintId(): int
    {
        return $this->blueprintId;
    }

    public function setBlueprintId(int $blueprintId): Module
    {
        $this->blueprintId = $blueprintId;

        return $this;
    }

    public function getGeometryId(): ?int
    {
        return $this->geometryId;
    }

    public function setGeometryId(?int $geometryId): Module
    {
        $this->geometryId = $geometryId;

        return $this;
    }

    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    public function setModuleId(int $moduleId): Module
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): Module
    {
        $this->options = $options;

        return $this;
    }
}
