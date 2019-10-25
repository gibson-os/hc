<?php
namespace GibsonOS\Module\Hc\Model;

use DateTime;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Event\Element;
use GibsonOS\Module\Hc\Model\Event\Trigger;

class Event extends AbstractModel
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
     * @var int
     */
    private $active;
    /**
     * @var int
     */
    private $async;
    /**
     * @var DateTime
     */
    private $modified;

    /**
     * @var Element[]
     */
    private $elements;
    /**
     * @var Trigger[]
     */
    private $triggers;

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'hc_event';
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
     * @return Event
     */
    public function setId(int $id): Event
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
     * @return Event
     */
    public function setName(string $name): Event
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getActive(): int
    {
        return $this->active;
    }

    /**
     * @param int $active
     * @return Event
     */
    public function setActive(int $active): Event
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return int
     */
    public function getAsync(): int
    {
        return $this->async;
    }

    /**
     * @param int $async
     * @return Event
     */
    public function setAsync(int $async): Event
    {
        $this->async = $async;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getModified(): DateTime
    {
        return $this->modified;
    }

    /**
     * @param DateTime $modified
     * @return Event
     */
    public function setModified(DateTime $modified): Event
    {
        $this->modified = $modified;
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
     * @param Element[]|AbstractModel[] $elements
     * @return Event
     */
    public function setElements(array $elements): Event
    {
        $this->elements = $elements;
        return $this;
    }

    /**
     * @param Element $element
     * @return Event
     */
    public function addElement(Element $element): Event
    {
        $this->elements[] = $element;
        return $this;
    }

    public function loadElements()
    {
        $this->setElements(
            $this->loadForeignRecords(
                Element::class,
                $this->getId(),
                Element::getTableName(),
                'event_id'
            )
        );
    }

    /**
     * @return Trigger[]
     */
    public function getTriggers(): array
    {
        return $this->triggers;
    }

    /**
     * @param Trigger[] $triggers
     * @return Event
     */
    public function setTriggers(array $triggers): Event
    {
        $this->triggers = $triggers;
        return $this;
    }

    /**
     * @param Trigger $trigger
     * @return Event
     */
    public function addTrigger(Trigger $trigger): Event
    {
        $this->triggers[] = $trigger;
        return $this;
    }

    public function loadTriggers()
    {
        $this->setElements(
            $this->loadForeignRecords(
                Trigger::class,
                $this->getId(),
                Trigger::getTableName(),
                'event_id'
            )
        );
    }
}