<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Enum\Blueprint\Type;
use GibsonOS\Module\Hc\Model\Blueprint\Geometry;

/**
 * @method Blueprint|null getParent()
 * @method Blueprint      setParent(Blueprint|null $blueprint)
 * @method Blueprint[]    getChildren()
 * @method Blueprint      addChildren(Blueprint[] $children)
 * @method Blueprint      setChildren(Blueprint[] $children)
 * @method Geometry[]     getGeometries()
 * @method Blueprint      addGeometries(Geometry[] $children)
 * @method Blueprint      setGeometries(Geometry[] $children)
 */
#[Table]
class Blueprint extends AbstractModel
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(length: 64)]
    private string $name;

    #[Column]
    private Type $type = Type::FRAME;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $parentId = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $top = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $left = 0;

    #[Constraint]
    protected ?Blueprint $parent = null;

    #[Constraint('parent', Blueprint::class)]
    protected array $children = [];

    #[Constraint('blueprint', Geometry::class)]
    protected array $geometries = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Blueprint
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Blueprint
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): Blueprint
    {
        $this->type = $type;

        return $this;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function setParentId(?int $parentId): Blueprint
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getTop(): int
    {
        return $this->top;
    }

    public function setTop(int $top): Blueprint
    {
        $this->top = $top;

        return $this;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function setLeft(int $left): Blueprint
    {
        $this->left = $left;

        return $this;
    }
}
