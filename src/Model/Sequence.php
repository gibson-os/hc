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
     * @var int|null
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

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'hc_sequence';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     *
     * @return Sequence
     */
    public function setId(?int $id): Sequence
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Sequence
     */
    public function setName(string $name): Sequence
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTypeId(): ?int
    {
        return $this->typeId;
    }

    /**
     * @param int|null $typeId
     *
     * @return Sequence
     */
    public function setTypeId(?int $typeId): Sequence
    {
        $this->typeId = $typeId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getModuleId(): ?int
    {
        return $this->moduleId;
    }

    /**
     * @param int|null $moduleId
     *
     * @return Sequence
     */
    public function setModuleId(?int $moduleId): Sequence
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * @param int|null $type
     *
     * @return Sequence
     */
    public function setType(?int $type): Sequence
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getAdded(): ?DateTime
    {
        return $this->added;
    }

    /**
     * @param DateTime|null $added
     *
     * @return Sequence
     */
    public function setAdded(?DateTime $added): Sequence
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return Type|null
     */
    public function getTypeModel(): ?Type
    {
        return $this->typeModel;
    }

    /**
     * @param Type|null $typeModel
     *
     * @return Sequence
     */
    public function setTypeModel(?Type $typeModel): Sequence
    {
        $this->typeModel = $typeModel;
        $typeId = 0;

        if ($typeModel instanceof Type) {
            $typeId = $typeModel->getId();
        }

        $this->setTypeId($typeId);

        return $this;
    }

    /**
     * @return Module|null
     */
    public function getModule(): ?Module
    {
        return $this->module;
    }

    /**
     * @param Module|null $module
     *
     * @return Sequence
     */
    public function setModule(?Module $module): Sequence
    {
        $this->module = $module;
        $moduleId = 0;

        if ($module instanceof Module) {
            $moduleId = $module->getId();
        }

        $this->setModuleId($moduleId);

        return $this;
    }

    /**
     * @return Element[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * @param Element[] $elements
     *
     * @return Sequence
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
     * @throws SelectError
     * @throws DateTimeError
     *
     * @return Sequence
     */
    public function loadType(): Sequence
    {
        if ($this->getTypeModel() instanceof Type) {
            $this->loadForeignRecord($this->getTypeModel(), (string) $this->getTypeId());
        }

        return $this;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     *
     * @return Sequence
     */
    public function loadModule(): Sequence
    {
        if ($this->getModule() instanceof Module) {
            $this->loadForeignRecord($this->getModule(), (string) $this->getModuleId());
        }

        return $this;
    }

    /**
     * @throws DateTimeError
     *
     * @return Sequence
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
