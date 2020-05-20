<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Event;

use DateTime;
use DateTimeInterface;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Event as EventModel;
use GibsonOS\Module\Hc\Model\Event\Element;
use GibsonOS\Module\Hc\Model\Event\Trigger;
use mysqlTable;
use stdClass;

class TriggerRepository extends AbstractRepository
{
    /**
     * @return Trigger[]
     */
    public function getByMasterId(int $masterId): array
    {
        $table = $this->initializeTable();
        $table->setWhere('`' . Trigger::getTableName() . '`.`master_id`=' . $masterId);

        if (!$table->select(false)) {
            return [];
        }

        return $this->matchModels($table->connection->fetchObjectList());
    }

    /**
     * @return Trigger[]
     */
    public function getByModuleId(int $moduleId): array
    {
        $table = $this->initializeTable();
        $table->setWhere('`' . Trigger::getTableName() . '`.`module_id`=' . $moduleId);

        if (!$table->select(false)) {
            return [];
        }

        return $this->matchModels($table->connection->fetchObjectList());
    }

    public function getByDateTime(DateTimeInterface $dateTime): array
    {
        $tableName = Trigger::getTableName();
        $table = $this->initializeTable();
        $table->setWhere(
            '(`' . $tableName . '`.`year` IS NULL OR `' . $tableName . '`.`year`=' . ((int) $dateTime->format('Y')) . ') AND ' .
            '(`' . $tableName . '`.`month` IS NULL OR `' . $tableName . '`.`month`=' . ((int) $dateTime->format('m')) . ') AND ' .
            '(`' . $tableName . '`.`weekday` IS NULL OR `' . $tableName . '`.`weekday`=' . ((int) $dateTime->format('w')) . ') AND ' .
            '(`' . $tableName . '`.`day` IS NULL OR `' . $tableName . '`.`day`=' . ((int) $dateTime->format('d')) . ') AND ' .
            '(`' . $tableName . '`.`hour` IS NULL OR `' . $tableName . '`.`hour`=' . ((int) $dateTime->format('H')) . ') AND ' .
            '(`' . $tableName . '`.`minute` IS NULL OR `' . $tableName . '`.`minute`=' . ((int) $dateTime->format('i')) . ')'
        );

        if (!$table->select(false)) {
            return [];
        }

        return $this->matchModels($table->connection->fetchObjectList());
    }

    /**
     * @return mysqlTable
     */
    private function initializeTable()
    {
        $table = $this->getTable(Element::getTableName());
        $table->appendJoin('`hc_event`', '`hc_event_element`.`event_id`=`hc_event`.`id`');
        $table->appendJoin('`hc_trigger`', '`hc_event_element`.`event_id`=`hc_event`.`id`');
        $table->setOrderBy('`hc_event_trigger`.`priority`, `hc_event_element`.`left`');
        $table->setSelectString([
            'eventId' => '`hc_event`.`id`',
            'name' => '`hc_event`.`name`',
            'active' => '`hc_event`.`active`',
            'async' => '`hc_event`.`async`',
            'modified' => '`hc_event`.`modified`',
            'elementId' => '`hc_event_element`.`id`',
            'elementLeft' => '`hc_event_element`.`left`',
            'elementRight' => '`hc_event_element`.`right`',
            'elementParentId' => '`hc_event_element`.`parent_id`',
            'elementMasterId' => '`hc_event_element`.`master_id`',
            'elementModuleId' => '`hc_event_element`.`module_id`',
            'elementClass' => '`hc_event_element`.`class`',
            'elementFunction' => '`hc_event_element`.`function`',
            'elementParams' => '`hc_event_element`.`params`',
            'elementCommand' => '`hc_event_element`.`command`',
            'elementOperator' => '`hc_event_element`.`operator`',
            'elementValue' => '`hc_event_element`.`value`',
            'triggerId' => '`hc_event_trigger`.`id`',
            'triggerTrigger' => '`hc_event_trigger`.`trigger`',
            'triggerMasterId' => '`hc_event_trigger`.`masterId`',
            'triggerModuleId' => '`hc_event_trigger`.`moduleId`',
            'triggerWeekday' => '`hc_event_trigger`.`weekday`',
            'triggerDay' => '`hc_event_trigger`.`day`',
            'triggerMonth' => '`hc_event_trigger`.`month`',
            'triggerYear' => '`hc_event_trigger`.`year`',
            'triggerHour' => '`hc_event_trigger`.`hour`',
            'triggerMinute' => '`hc_event_trigger`.`minute`',
            'triggerPriority' => '`hc_event_trigger`.`priority`',
        ]);

        return $table;
    }

    /**
     * @param stdClass[] $events
     *
     * @return Trigger[]
     */
    private function matchModels($events)
    {
        /**
         * @var Trigger[]
         */
        $models = [];
        /**
         * @var EventModel[]
         */
        $eventModels = [];
        $elementModels = [];

        foreach ($events as $event) {
            if (!isset($eventModels[$event->id])) {
                $eventModels[$event->id] = (new EventModel())
                    ->setId($event->id)
                    ->setName($event->name)
                    ->setActive($event->active)
                    ->setAsync($event->async)
                    ->setModified(new DateTime($event->modified));
            }

            if (!isset($models[$event->triggerId])) {
                $models[$event->triggerId] = (new Trigger())
                    ->setId($event->triggerId)
                    ->setEvent($eventModels[$event->id])
                    ->setMasterId($event->triggerMasterId)
                    ->setModuleId($event->triggerModuleId)
                    ->setWeekday($event->triggerModuleId)
                    ->setDay($event->triggerDay)
                    ->setMonth($event->triggerMonth)
                    ->setYear($event->triggerYear)
                    ->setHour($event->triggerHour)
                    ->setMinute($event->triggerMinute)
                    ->setPriority($event->triggerPriority);
                $eventModels[$event->id]->addTrigger($models[$event->triggerId]);
            }

            $elementModel = (new Element())
                ->setId($event->elementId)
                ->setEvent($eventModels[$event->id])
                ->setLeft($event->elementLeft)
                ->setRight($event->elementRight)
                ->setParent($elementModels[$event->elementParentId])
                ->setMasterId($event->elementMasterId)
                ->setModuleId($event->elementModuleId)
                ->setClass($event->elementClass)
                ->setFunction($event->elementFunction)
                ->setParams($event->elementParams)
                ->setCommand($event->elementCommand)
                ->setOperator($event->elementOperator)
                ->setValue($event->elementValue);
            $elementModels[$event->elementId] = $elementModel;
            $eventModels[$event->id]->addElement($elementModel);
        }

        return $models;
    }
}
