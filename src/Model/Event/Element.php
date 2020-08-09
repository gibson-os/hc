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
use Serializable;

class Element extends AbstractModel implements Serializable
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
     * @var int|null
     */
    private $parentId;

    /**
     * @var int|null
     */
    private $masterId;

    /**
     * @var int|null
     */
    private $moduleId;

    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string|null
     */
    private $params;

    /**
     * @var string|null
     */
    private $command;

    /**
     * @var string|null
     */
    private $operator;

    /**
     * @var string|null
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
     * @var Master|null
     */
    private $master;

    /**
     * @var Module|null
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

    public function setId(?int $id): Element
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

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function setParentId(?int $parentId): Element
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getMasterId(): ?int
    {
        return $this->masterId;
    }

    public function setMasterId(?int $masterId): Element
    {
        $this->masterId = $masterId;

        return $this;
    }

    public function getModuleId(): ?int
    {
        return $this->moduleId;
    }

    public function setModuleId(?int $moduleId): Element
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

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): Element
    {
        $this->method = $method;

        return $this;
    }

    public function getParams(): ?string
    {
        return $this->params;
    }

    public function setParams(?string $params): Element
    {
        $this->params = $params;

        return $this;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function setCommand(?string $command): Element
    {
        $this->command = $command;

        return $this;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function setOperator(?string $operator): Element
    {
        $this->operator = $operator;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): Element
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

    public function setParent(?Element $parent): Element
    {
        $this->parent = $parent;
        $this->setParentId($parent instanceof Element ? (int) $parent->getId() : null);

        return $this;
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     */
    public function getMaster(): ?Master
    {
        if ($this->master instanceof Master) {
            $this->loadForeignRecord($this->master, $this->getMasterId());
        }

        return $this->master;
    }

    public function setMaster(?Master $master): Element
    {
        $this->master = $master;
        $this->setMasterId($master instanceof Master ? (int) $master->getId() : null);

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

    public function setModule(?Module $module): Element
    {
        $this->module = $module;
        $this->setModuleId($module instanceof Module ? (int) $module->getId() : null);

        return $this;
    }

    public function serialize(): string
    {
        return serialize([
            'id' => $this->getId(),
            'eventId' => $this->getEventId(),
            'masterId' => $this->getMasterId(),
            'moduleId' => $this->getModuleId(),
            'parentId' => $this->getParentId(),
            'left' => $this->getLeft(),
            'right' => $this->getRight(),
            'command' => $this->getCommand(),
            'class' => $this->getClass(),
            'method' => $this->getMethod(),
            'operator' => $this->getOperator(),
            'params' => $this->getParams(),
            'value' => $this->getValue(),
        ]);
    }

    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this
            ->setId($unserialized['id'])
            ->setEventId($unserialized['eventId'])
            ->setMasterId($unserialized['masterId'])
            ->setModuleId($unserialized['moduleId'])
            ->setParentId($unserialized['parentId'])
            ->setLeft($unserialized['left'])
            ->setRight($unserialized['right'])
            ->setCommand($unserialized['command'])
            ->setClass($unserialized['class'])
            ->setMethod($unserialized['method'])
            ->setOperator($unserialized['operator'])
            ->setParams($unserialized['params'])
            ->setValue($unserialized['value'])
        ;
    }
}
