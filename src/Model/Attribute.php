<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTime;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AbstractModel;
use mysqlDatabase;

class Attribute extends AbstractModel
{
    /**
     * @var int|null
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

    /**
     * @param mysqlDatabase|null $database
     */
    public function __construct(mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->module = new Module();
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'hc_attribute';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Attribute
     */
    public function setId(int $id): Attribute
    {
        $this->id = $id;

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
     * @return Attribute
     */
    public function setTypeId(?int $typeId): Attribute
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
     * @return Attribute
     */
    public function setModuleId(?int $moduleId): Attribute
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSubId(): ?int
    {
        return $this->subId;
    }

    /**
     * @param int|null $subId
     *
     * @return Attribute
     */
    public function setSubId(?int $subId): Attribute
    {
        $this->subId = $subId;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return Attribute
     */
    public function setKey(string $key): Attribute
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     *
     * @return Attribute
     */
    public function setType(?string $type): Attribute
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
     * @return Attribute
     */
    public function setAdded(?DateTime $added): Attribute
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return Module
     */
    public function getModule(): Module
    {
        return $this->module;
    }

    /**
     * @param Module $module
     *
     * @return Attribute
     */
    public function setModule(Module $module): Attribute
    {
        $this->module = $module;
        $this->setModuleId($module->getId());
        $this->setTypeId($module->getTypeId());

        return $this;
    }

    /**
     * @throws SelectError
     *
     * @return Attribute
     */
    public function loadModule(): Attribute
    {
        $this->loadForeignRecord($this->getModule(), $this->getModuleId());
        $this->setModule($this->getModule());

        return $this;
    }
}
