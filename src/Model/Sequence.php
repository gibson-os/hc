<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTimeImmutable;
use DateTimeInterface;
use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Model\Sequence\Element;
use JsonSerializable;
use mysqlDatabase;

#[Table]
class Sequence extends AbstractModel implements JsonSerializable, AutoCompleteModelInterface
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(length: 32)]
    private string $name;

    #[Column(type: Column::TYPE_SMALLINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $typeId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $moduleId = null;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $type = null;

    #[Column(type: Column::TYPE_TIMESTAMP, default: Column::DEFAULT_CURRENT_TIMESTAMP)]
    private DateTimeInterface $added;

    private Type $typeModel;

    private ?Module $module = null;

    /**
     * @var Element[]|null
     */
    private ?array $elements = null;

    public function __construct(mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->typeModel = new Type();
        $this->added = new DateTimeImmutable();
    }

    public static function getTableName(): string
    {
        return 'hc_sequence';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Sequence
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Sequence
    {
        $this->name = $name;

        return $this;
    }

    public function getTypeId(): int
    {
        return $this->typeId;
    }

    public function setTypeId(int $typeId): Sequence
    {
        $this->typeId = $typeId;

        return $this;
    }

    public function getModuleId(): ?int
    {
        return $this->moduleId;
    }

    public function setModuleId(?int $moduleId): Sequence
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(?int $type): Sequence
    {
        $this->type = $type;

        return $this;
    }

    public function getAdded(): DateTimeInterface
    {
        return $this->added;
    }

    public function setAdded(DateTimeInterface $added): Sequence
    {
        $this->added = $added;

        return $this;
    }

    public function getTypeModel(): ?Type
    {
        $typeId = $this->getTypeId();
        $this->typeModel = new Type();
        $this->loadForeignRecord($this->typeModel, $typeId);

        return $this->typeModel;
    }

    public function setTypeModel(Type $typeModel): Sequence
    {
        $this->typeModel = $typeModel;
        $this->setTypeId($typeModel->getId() ?? 0);

        return $this;
    }

    public function getModule(): ?Module
    {
        $moduleId = $this->getModuleId();

        if ($moduleId !== null) {
            $this->module = new Module();
            $this->loadForeignRecord($this->module, $moduleId);
        }

        return $this->module;
    }

    public function setModule(?Module $module): Sequence
    {
        $this->module = $module;
        $moduleId = null;

        if ($module instanceof Module) {
            $moduleId = $module->getId();
        }

        $this->setModuleId($moduleId);

        return $this;
    }

    /**
     * @return Element[]
     */
    public function getElements(): ?array
    {
        if ($this->elements === null) {
            $this->loadElements();
        }

        return $this->elements;
    }

    /**
     * @param Element[] $elements
     */
    public function setElements(?array $elements): Sequence
    {
        $this->elements = $elements;

        return $this;
    }

    public function addElement(Element $element): Sequence
    {
        $this->elements[] = $element;

        return $this;
    }

    public function loadElements(): Sequence
    {
        /** @var Element[] $elements */
        $elements = $this->loadForeignRecords(
            Element::class,
            $this->getId(),
            Element::getTableName(),
            'sequence_id'
        );

        $this->setElements($elements);

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'typeId' => $this->getTypeId(),
            'moduleId' => $this->getModuleId(),
            'type' => $this->getType(),
        ];
    }

    public function getAutoCompleteId(): int
    {
        return $this->getId() ?? 0;
    }
}
