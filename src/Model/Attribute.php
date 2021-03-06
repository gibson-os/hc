<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTimeImmutable;
use DateTimeInterface;
use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use mysqlDatabase;
use ReflectionException;

/**
 * @method Module    getModule()
 * @method Type      getTypeModel()
 * @method Attribute setTypeModel(Type $type)
 * @method Value[]   getValues()
 * @method Attribute setValues(Value[] $values)
 * @method Attribute addValues(Value[] $values)
 */
#[Table]
#[Key(unique: true, columns: ['type_id', 'module_id', 'sub_id', 'key', 'type'])]
class Attribute extends AbstractModel
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(type: Column::TYPE_SMALLINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $typeId = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $moduleId = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $subId = null;

    #[Column(length: 64)]
    #[Key]
    private string $key;

    #[Column(length: 64)]
    private ?string $type = null;

    #[Column(type: Column::TYPE_TIMESTAMP, default: Column::DEFAULT_CURRENT_TIMESTAMP)]
    private DateTimeInterface $added;

    #[Constraint]
    protected ?Module $module = null;

    #[Constraint(onDelete: null, ownColumn: 'type_id')]
    protected Type $typeModel;

    #[Constraint('attribute', Value::class, orderBy: '`order`')]
    protected array $values;

    public function __construct(mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->added = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Attribute
    {
        $this->id = $id;

        return $this;
    }

    public function getTypeId(): ?int
    {
        return $this->typeId;
    }

    public function setTypeId(?int $typeId): Attribute
    {
        $this->typeId = $typeId;

        return $this;
    }

    public function getModuleId(): ?int
    {
        return $this->moduleId;
    }

    public function setModuleId(?int $moduleId): Attribute
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getSubId(): ?int
    {
        return $this->subId;
    }

    public function setSubId(?int $subId): Attribute
    {
        $this->subId = $subId;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): Attribute
    {
        $this->key = $key;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): Attribute
    {
        $this->type = $type;

        return $this;
    }

    public function getAdded(): DateTimeInterface
    {
        return $this->added;
    }

    public function setAdded(DateTimeInterface $added): Attribute
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function setModule(?Module $module): Attribute
    {
        $this->__call('setModule', [$module]);

        if ($module !== null) {
            $this->setTypeId($module->getTypeId());
        }

        return $this;
    }
}
