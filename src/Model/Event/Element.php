<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Event;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Event;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use mysqlDatabase;

class Element extends AbstractModel
{
    /**
     * @var int
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
     * @var Element
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

    /**
     * @param mysqlDatabase|null $database
     */
    public function __construct(mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->event = new Event();
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'hc_event_element';
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Element
     */
    public function setId(int $id): Element
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getEventId(): int
    {
        return $this->eventId;
    }

    /**
     * @param int $eventId
     *
     * @return Element
     */
    public function setEventId(int $eventId): Element
    {
        $this->eventId = $eventId;

        return $this;
    }

    /**
     * @return int
     */
    public function getLeft(): int
    {
        return $this->left;
    }

    /**
     * @param int $left
     *
     * @return Element
     */
    public function setLeft(int $left): Element
    {
        $this->left = $left;

        return $this;
    }

    /**
     * @return int
     */
    public function getRight(): int
    {
        return $this->right;
    }

    /**
     * @param int $right
     *
     * @return Element
     */
    public function setRight(int $right): Element
    {
        $this->right = $right;

        return $this;
    }

    /**
     * @return int
     */
    public function getParentId(): int
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     *
     * @return Element
     */
    public function setParentId(int $parentId): Element
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * @return int
     */
    public function getMasterId(): int
    {
        return $this->masterId;
    }

    /**
     * @param int $masterId
     *
     * @return Element
     */
    public function setMasterId(int $masterId): Element
    {
        $this->masterId = $masterId;

        return $this;
    }

    /**
     * @return int
     */
    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    /**
     * @param int $moduleId
     *
     * @return Element
     */
    public function setModuleId(int $moduleId): Element
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     *
     * @return Element
     */
    public function setClass(string $class): Element
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * @param string $function
     *
     * @return Element
     */
    public function setFunction(string $function): Element
    {
        $this->function = $function;

        return $this;
    }

    /**
     * @return string
     */
    public function getParams(): string
    {
        return $this->params;
    }

    /**
     * @param string $params
     *
     * @return Element
     */
    public function setParams(string $params): Element
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     *
     * @return Element
     */
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

    /**
     * @param string $operator
     *
     * @return Element
     */
    public function setOperator(string $operator): Element
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return Element
     */
    public function setValue(string $value): Element
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * @param Event $event
     *
     * @return Element
     */
    public function setEvent(Event $event): Element
    {
        $this->event = $event;
        $this->setEventId($event->getId());

        return $this;
    }

    /**
     * @throws SelectError
     *
     * @return Element
     */
    public function loadEvent(): Element
    {
        $this->loadForeignRecord($this->getEvent(), $this->getEventId());
        $this->setEvent($this->getEvent());

        return $this;
    }

    /**
     * @return Element
     */
    public function getParent(): Element
    {
        return $this->parent;
    }

    /**
     * @param Element $parent
     *
     * @return Element
     */
    public function setParent(Element $parent): Element
    {
        $this->parent = $parent;
        $this->setParentId($parent->getId());

        return $this;
    }

    /**
     * @throws SelectError
     *
     * @return Element
     */
    public function loadParent(): Element
    {
        $this->loadForeignRecord($this->getParent(), $this->getParentId());
        $this->setParent($this->getParent());

        return $this;
    }

    /**
     * @return Master
     */
    public function getMaster(): Master
    {
        return $this->master;
    }

    /**
     * @param Master $master
     *
     * @return Element
     */
    public function setMaster(Master $master): Element
    {
        $this->master = $master;
        $this->setMasterId($master->getId());

        return $this;
    }

    /**
     * @throws SelectError
     *
     * @return Element
     */
    public function loadMaster(): Element
    {
        $this->loadForeignRecord($this->getMaster(), $this->getMasterId());
        $this->setMaster($this->getMaster());

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
     * @return Element
     */
    public function setModule(Module $module): Element
    {
        $this->module = $module;
        $this->setModuleId($module->getId());

        return $this;
    }

    /**
     * @throws SelectError
     *
     * @return Element
     */
    public function loadModule(): Element
    {
        $this->loadForeignRecord($this->getModule(), $this->getModuleId());
        $this->setModule($this->getModule());

        return $this;
    }
}
