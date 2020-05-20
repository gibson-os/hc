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

class Trigger extends AbstractModel
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
     * @var string
     */
    private $trigger;

    /**
     * @var int|null
     */
    private $masterId;

    /**
     * @var int|null
     */
    private $moduleId;

    /**
     * @var int|null
     */
    private $weekday;

    /**
     * @var int|null
     */
    private $day;

    /**
     * @var int|null
     */
    private $month;

    /**
     * @var int|null
     */
    private $year;

    /**
     * @var int|null
     */
    private $hour;

    /**
     * @var int|null
     */
    private $minute;

    /**
     * @var int|null
     */
    private $second;

    /**
     * @var int|null
     */
    private $priority;

    /**
     * @var Event
     */
    private $event;

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
    }

    public static function getTableName(): string
    {
        return 'hc_event_trigger';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Trigger
    {
        $this->id = $id;

        return $this;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function setEventId(int $eventId): Trigger
    {
        $this->eventId = $eventId;

        return $this;
    }

    public function getTrigger(): string
    {
        return $this->trigger;
    }

    public function setTrigger(string $trigger): Trigger
    {
        $this->trigger = $trigger;

        return $this;
    }

    public function getMasterId(): ?int
    {
        return $this->masterId;
    }

    public function setMasterId(?int $masterId): Trigger
    {
        $this->masterId = $masterId;

        return $this;
    }

    public function getModuleId(): ?int
    {
        return $this->moduleId;
    }

    public function setModuleId(?int $moduleId): Trigger
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getWeekday(): ?int
    {
        return $this->weekday;
    }

    public function setWeekday(?int $weekday): Trigger
    {
        $this->weekday = $weekday;

        return $this;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(?int $day): Trigger
    {
        $this->day = $day;

        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(?int $month): Trigger
    {
        $this->month = $month;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): Trigger
    {
        $this->year = $year;

        return $this;
    }

    public function getHour(): ?int
    {
        return $this->hour;
    }

    public function setHour(?int $hour): Trigger
    {
        $this->hour = $hour;

        return $this;
    }

    public function getMinute(): ?int
    {
        return $this->minute;
    }

    public function setMinute(?int $minute): Trigger
    {
        $this->minute = $minute;

        return $this;
    }

    public function getSecond(): ?int
    {
        return $this->second;
    }

    public function setSecond(?int $second): Trigger
    {
        $this->second = $second;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): Trigger
    {
        $this->priority = $priority;

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

    public function setEvent(Event $event): Trigger
    {
        $this->event = $event;
        $this->setEventId((int) $event->getId());

        return $this;
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     */
    public function getMaster(): ?Master
    {
        if ($this->master !== null) {
            $this->loadForeignRecord($this->master, $this->getMasterId());
        }

        return $this->master;
    }

    public function setMaster(?Master $master): Trigger
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
        if ($this->module !== null) {
            $this->loadForeignRecord($this->module, $this->getModuleId());
        }

        return $this->module;
    }

    public function setModule(?Module $module): Trigger
    {
        $this->module = $module;
        $this->setModuleId($module instanceof Module ? (int) $module->getId() : null);

        return $this;
    }
}
