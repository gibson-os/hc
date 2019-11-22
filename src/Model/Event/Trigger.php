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
     * @var int
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
     * @var int
     */
    private $masterId;

    /**
     * @var int
     */
    private $moduleId;

    /**
     * @var int
     */
    private $weekday;

    /**
     * @var int
     */
    private $day;

    /**
     * @var int
     */
    private $month;

    /**
     * @var int
     */
    private $year;

    /**
     * @var int
     */
    private $hour;

    /**
     * @var int
     */
    private $minute;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var Event
     */
    private $event;

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
        return 'hc_event_trigger';
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
     * @return Trigger
     */
    public function setId(int $id): Trigger
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
     * @return Trigger
     */
    public function setEventId(int $eventId): Trigger
    {
        $this->eventId = $eventId;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrigger(): string
    {
        return $this->trigger;
    }

    /**
     * @param string $trigger
     *
     * @return Trigger
     */
    public function setTrigger(string $trigger): Trigger
    {
        $this->trigger = $trigger;

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
     * @return Trigger
     */
    public function setMasterId(int $masterId): Trigger
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
     * @return Trigger
     */
    public function setModuleId(int $moduleId): Trigger
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    /**
     * @return int
     */
    public function getWeekday(): int
    {
        return $this->weekday;
    }

    /**
     * @param int $weekday
     *
     * @return Trigger
     */
    public function setWeekday(int $weekday): Trigger
    {
        $this->weekday = $weekday;

        return $this;
    }

    /**
     * @return int
     */
    public function getDay(): int
    {
        return $this->day;
    }

    /**
     * @param int $day
     *
     * @return Trigger
     */
    public function setDay(int $day): Trigger
    {
        $this->day = $day;

        return $this;
    }

    /**
     * @return int
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * @param int $month
     *
     * @return Trigger
     */
    public function setMonth(int $month): Trigger
    {
        $this->month = $month;

        return $this;
    }

    /**
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * @param int $year
     *
     * @return Trigger
     */
    public function setYear(int $year): Trigger
    {
        $this->year = $year;

        return $this;
    }

    /**
     * @return int
     */
    public function getHour(): int
    {
        return $this->hour;
    }

    /**
     * @param int $hour
     *
     * @return Trigger
     */
    public function setHour(int $hour): Trigger
    {
        $this->hour = $hour;

        return $this;
    }

    /**
     * @return int
     */
    public function getMinute(): int
    {
        return $this->minute;
    }

    /**
     * @param int $minute
     *
     * @return Trigger
     */
    public function setMinute(int $minute): Trigger
    {
        $this->minute = $minute;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     *
     * @return Trigger
     */
    public function setPriority(int $priority): Trigger
    {
        $this->priority = $priority;

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
     * @return Trigger
     */
    public function setEvent(Event $event): Trigger
    {
        $this->event = $event;
        $this->setEventId($event->getId());

        return $this;
    }

    /**
     * @throws SelectError
     *
     * @return Trigger
     */
    public function loadEvent(): Trigger
    {
        $this->loadForeignRecord($this->getEvent(), $this->getEventId());
        $this->setEvent($this->getEvent());

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
     * @return Trigger
     */
    public function setMaster(Master $master): Trigger
    {
        $this->master = $master;
        $this->setMasterId((int) $master->getId());

        return $this;
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     *
     * @return Trigger
     */
    public function loadMaster(): Trigger
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
     * @return Trigger
     */
    public function setModule(Module $module): Trigger
    {
        $this->module = $module;
        $this->setModuleId((int) $module->getId());

        return $this;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     *
     * @return Trigger
     */
    public function loadModule(): Trigger
    {
        $this->loadForeignRecord($this->getModule(), $this->getModuleId());
        $this->setModule($this->getModule());

        return $this;
    }
}
