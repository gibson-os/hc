<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTime;
use GibsonOS\Core\Exception\DateTimeError;
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

    public static function getTableName(): string
    {
        return 'hc_event';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Event
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Event
    {
        $this->name = $name;

        return $this;
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function setActive(int $active): Event
    {
        $this->active = $active;

        return $this;
    }

    public function getAsync(): int
    {
        return $this->async;
    }

    public function setAsync(int $async): Event
    {
        $this->async = $async;

        return $this;
    }

    public function getModified(): DateTime
    {
        return $this->modified;
    }

    public function setModified(DateTime $modified): Event
    {
        $this->modified = $modified;

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
    public function setElements(array $elements): Event
    {
        $this->elements = $elements;

        return $this;
    }

    public function addElement(Element $element): Event
    {
        $this->elements[] = $element;

        return $this;
    }

    /**
     * @throws DateTimeError
     */
    public function loadElements()
    {
        /** @var Element[] $elements */
        $elements = $this->loadForeignRecords(
            Element::class,
            $this->getId(),
            Element::getTableName(),
            'event_id'
        );

        $this->setElements($elements);
    }

    /**
     * @throws DateTimeError
     *
     * @return Trigger[]
     */
    public function getTriggers(): array
    {
        if ($this->triggers === null) {
            $this->loadTriggers();
        }

        return $this->triggers;
    }

    /**
     * @param Trigger[] $triggers
     */
    public function setTriggers(array $triggers): Event
    {
        $this->triggers = $triggers;

        return $this;
    }

    public function addTrigger(Trigger $trigger): Event
    {
        $this->triggers[] = $trigger;

        return $this;
    }

    /**
     * @throws DateTimeError
     */
    public function loadTriggers()
    {
        /** @var Trigger[] $triggers */
        $triggers = $this->loadForeignRecords(
            Trigger::class,
            $this->getId(),
            Trigger::getTableName(),
            'event_id'
        );

        $this->setTriggers($triggers);
    }
}
