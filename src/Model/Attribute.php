<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTime;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AbstractModel;
use mysqlDatabase;

class Attribute extends AbstractModel
{
    /**
     * @var int
     */
    private $id;

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
    private $subId;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var DateTime|null
     */
    private $added;

    /**
     * @var Module
     */
    private $module;

    public function __construct(mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->module = new Module();
    }

    public static function getTableName(): string
    {
        return 'hc_attribute';
    }

    public function getId(): int
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

    public function getAdded(): ?DateTime
    {
        return $this->added;
    }

    public function setAdded(?DateTime $added): Attribute
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getModule(): Module
    {
        $this->loadForeignRecord($this->module, $this->getModuleId());

        return $this->module;
    }

    public function setModule(Module $module): Attribute
    {
        $this->module = $module;
        $this->setModuleId($module->getId());
        $this->setTypeId($module->getTypeId());

        return $this;
    }
}
