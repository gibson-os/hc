<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Event;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Event;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use mysqlDatabase;

class Element extends AbstractModel
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var int
     */
    private $eventId;

    /**
     * @var int
     */
    private $left;

    /**
     * @var int
     */
    private $right;

    /**
     * @var int
     */
    private $parentId;

    /**
     * @var int
     */
    private $masterId;

    /**
     * @var int
     */
    private $moduleId;

    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $function;

    /**
     * @var string
     */
    private $params;

    /**
     * @var string
     */
    private $command;

    /**
     * @var string
     */
    private $operator;

    /**
     * @var string
     */
    private $value;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var Element|null
     */
    private $parent;

    /**
     * @var Master
     */
    private $master;

    /**
     * @var Module
     */
    private $module;

    public function __construct(mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->event = new Event();
        $this->master = new Master();
        $this->module = new Module();
    }

    public static function getTableName(): string
    {
        return 'hc_event_element';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Element
    {
        $this->id = $id;

        return $this;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function setEventId(int $eventId): Element
    {
        $this->eventId = $eventId;

        return $this;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function setLeft(int $left): Element
    {
        $this->left = $left;

        return $this;
    }

    public function getRight(): int
    {
        return $this->right;
    }

    public function setRight(int $right): Element
    {
        $this->right = $right;

        return $this;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId): Element
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getMasterId(): int
    {
        return $this->masterId;
    }

    public function setMasterId(int $masterId): Element
    {
        $this->masterId = $masterId;

        return $this;
    }

    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    public function setModuleId(int $moduleId): Element
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setClass(string $class): Element
    {
        $this->class = $class;

        return $this;
    }

    public function getFunction(): string
    {
        return $this->function;
    }

    public function setFunction(string $function): Element
    {
        $this->function = $function;

        return $this;
    }

    public function getParams(): string
    {
        return $this->params;
    }

    public function setParams(string $params): Element
    {
        $this->params = $params;

        return $this;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): Element
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    public function setOperator(string $operator): Element
    {
        $this->operator = $operator;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Element
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getEvent(): Event
    {
        $this->loadForeignRecord($this->event, $this->getEventId());

        return $this->event;
    }

    public function setEvent(Event $event): Element
    {
        $this->event = $event;
        $this->setEventId((int) $event->getId());

        return $this;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getParent(): ?Element
    {
        if ($this->parent instanceof Element) {
            $this->loadForeignRecord($this->parent, $this->getParentId());
        }

        return $this->parent;
    }

    public function setParent(Element $parent): Element
    {
        $this->parent = $parent;
        $this->setParentId((int) $parent->getId());

        return $this;
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     */
    public function getMaster(): Master
    {
        $this->loadForeignRecord($this->master, $this->getMasterId());

        return $this->master;
    }

    public function setMaster(Master $master): Element
    {
        $this->master = $master;
        $this->setMasterId((int) $master->getId());

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

    public function setModule(Module $module): Element
    {
        $this->module = $module;
        $this->setModuleId((int) $module->getId());

        return $this;
    }
}
