<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTime;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Sequence\Element;

class Sequence extends AbstractModel
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int|null
     */
    private $typeId;

    /**
     * @var int|null
     */
    private $moduleId;

    /**
     * @var int|null
     */
    private $type;

    /**
     * @var DateTime|null
     */
    private $added;

    /**
     * @var Type|null
     */
    private $typeModel;

    /**
     * @var Module|null
     */
    private $module;

    /**
     * @var Element[]
     */
    private $elements = [];

    public static function getTableName(): string
    {
        return 'hc_sequence';
    }

    public function getId(): int
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

    public function getAdded(): ?DateTime
    {
        return $this->added;
    }

    public function setAdded(?DateTime $added): Sequence
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getTypeModel(): ?Type
    {
        if ($this->typeModel instanceof Type) {
            $this->loadForeignRecord($this->typeModel, $this->getTypeId());
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

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getModule(): ?Module
    {
        if ($this->module instanceof Module) {
            $this->loadForeignRecord($this->module, $this->getModuleId());
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
     * @throws DateTimeError
     *
     * @return Element[]
     */
    public function getElements(): array
    {
        if ($this->elements === null) {
            $this->loadElements();
        }

        return $this->elements;
    }

    /**
     * @param Element[] $elements
     */
    public function setElements(array $elements): Sequence
    {
        $this->elements = $elements;

        return $this;
    }

    public function addElement(Element $element): Sequence
    {
        $this->elements[] = $element;

        return $this;
    }

    /**
     * @throws DateTimeError
     */
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
}
