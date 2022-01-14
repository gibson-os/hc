<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTimeImmutable;
use DateTimeInterface;
use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use mysqlDatabase;

/**
 * @method Module getModule()
 */
#[Table]
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
    private string $key;

    #[Column(length: 64)]
    private ?string $type = null;

    #[Column(type: Column::TYPE_TIMESTAMP, default: Column::DEFAULT_CURRENT_TIMESTAMP)]
    private DateTimeInterface $added;

    #[Constraint]
    protected Module $module;

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

    public function setModule(Module $module): Attribute
    {
        $this->module = $module;
        $this->setModuleId($module->getId());
        $this->setTypeId($module->getTypeId());

        return $this;
    }
}
