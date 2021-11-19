<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTimeInterface;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Model\Sequence\Element;
use JsonSerializable;

class Sequence extends AbstractModel implements JsonSerializable, AutoCompleteModelInterface
{
    private ?int $id = null;

    private string $name;

    private ?int $typeId = null;

    private ?int $moduleId = null;

    private ?int $type = null;

    private ?DateTimeInterface $added = null;

    private ?Type $typeModel = null;

    private ?Module $module = null;

    /**
     * @var Element[]|null
     */
    private ?array $elements = null;

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

    public function getTypeId(): ?int
    {
        return $this->typeId;
    }

    public function setTypeId(?int $typeId): Sequence
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

    public function getAdded(): ?DateTimeInterface
    {
        return $this->added;
    }

    public function setAdded(?DateTimeInterface $added): Sequence
    {
        $this->added = $added;

        return $this;
    }

    public function getTypeModel(): ?Type
    {
        $typeId = $this->getTypeId();

        if ($typeId !== null) {
            $this->typeModel = new Type();
            $this->loadForeignRecord($this->typeModel, $typeId);
        }

        return $this->typeModel;
    }

    public function setTypeModel(?Type $typeModel): Sequence
    {
        $this->typeModel = $typeModel;
        $typeId = null;

        if ($typeModel instanceof Type) {
            $typeId = $typeModel->getId();
        }

        $this->setTypeId($typeId);

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
