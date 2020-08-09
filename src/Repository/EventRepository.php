<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use DateTime;
use DateTimeInterface;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Event as EventModel;
use GibsonOS\Module\Hc\Model\Event\Element;
use GibsonOS\Module\Hc\Model\Event\Trigger;
use mysqlTable;
use stdClass;

class EventRepository extends AbstractRepository
{
    /**
     * @return EventModel[]
     */
    public function getByMasterId(int $masterId): array
    {
        $table = $this->initializeTable();
        $table->setWhere('`hc_event_trigger`.`master_id`=' . $masterId);

        if (!$table->select(false)) {
            return [];
        }

        return $this->matchModels($table->connection->fetchObjectList());
    }

    /**
     * @return EventModel[]
     */
    public function getByModuleId(int $masterId): array
    {
        $table = $this->initializeTable();
        $table->setWhere('`hc_event_trigger`.`module_id`=' . $masterId);

        if (!$table->select(false)) {
            return [];
        }

        return $this->matchModels($table->connection->fetchObjectList());
    }

    /**
     * @return EventModel[]
     */
    public function getTimeControlled(DateTimeInterface $dateTime): array
    {
        $table = $this->initializeTable();
        $table->setWhere(
            '`hc_event_trigger`.`trigger`=' . $this->escape(Trigger::TRIGGER_CRON) . ' AND ' .
            '(`hc_event_trigger`.`weekday` IS NULL OR `hc_event_trigger`.`weekday`=' . (int) $dateTime->format('w') . ') AND ' .
            '(`hc_event_trigger`.`day` IS NULL OR `hc_event_trigger`.`day`=' . (int) $dateTime->format('j') . ') AND ' .
            '(`hc_event_trigger`.`month` IS NULL OR `hc_event_trigger`.`month`=' . (int) $dateTime->format('n') . ') AND ' .
            '(`hc_event_trigger`.`year` IS NULL OR `hc_event_trigger`.`year`=' . (int) $dateTime->format('Y') . ') AND ' .
            '(`hc_event_trigger`.`hour` IS NULL OR `hc_event_trigger`.`hour`=' . (int) $dateTime->format('H') . ') AND ' .
            '(`hc_event_trigger`.`minute` IS NULL OR `hc_event_trigger`.`minute`=' . (int) $dateTime->format('m') . ') AND ' .
            '(`hc_event_trigger`.`second` IS NULL OR `hc_event_trigger`.`second`=' . (int) $dateTime->format('s') . ')'
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
        $table->appendJoin('`hc_event_trigger`', '`hc_event_element`.`event_id`=`hc_event_trigger`.`event_id`');
        $table->setOrderBy('`hc_event_trigger`.`priority` DESC, `hc_event_element`.`left`');
        $table->setSelectString(
            '`hc_event`.`id`, ' .
            '`hc_event`.`name`, ' .
            '`hc_event`.`active`, ' .
            '`hc_event`.`async`, ' .
            '`hc_event`.`modified`, ' .
            '`hc_event_element`.`id` AS `elementId`, ' .
            '`hc_event_element`.`left` AS `elementLeft`, ' .
            '`hc_event_element`.`right` AS `elementRight`, ' .
            '`hc_event_element`.`parent_id` AS `elementParentId`, ' .
            '`hc_event_element`.`master_id` AS `elementMasterId`, ' .
            '`hc_event_element`.`module_id` AS `elementModuleId`, ' .
            '`hc_event_element`.`class` AS `elementClass`, ' .
            '`hc_event_element`.`method` AS `elementMethod`, ' .
            '`hc_event_element`.`params` AS `elementParams`, ' .
            '`hc_event_element`.`command` AS `elementCommand`, ' .
            '`hc_event_element`.`operator` AS `elementOperator`, ' .
            '`hc_event_element`.`value` AS `elementValue`, ' .
            '`hc_event_trigger`.`id` AS `triggerId`, ' .
            '`hc_event_trigger`.`trigger` AS `triggerTrigger`, ' .
            '`hc_event_trigger`.`master_id` AS `triggerMasterId`, ' .
            '`hc_event_trigger`.`module_id` AS `triggerModuleId`, ' .
            '`hc_event_trigger`.`weekday` AS `triggerWeekday`, ' .
            '`hc_event_trigger`.`day` AS `triggerDay`, ' .
            '`hc_event_trigger`.`month` AS `triggerMonth`, ' .
            '`hc_event_trigger`.`year` AS `triggerYear`, ' .
            '`hc_event_trigger`.`hour` AS `triggerHour`, ' .
            '`hc_event_trigger`.`minute` AS `triggerMinute`, ' .
            '`hc_event_trigger`.`priority` AS `triggerPriority`'
        );

        return $table;
    }

    /**
     * @param stdClass[] $events
     *
     * @return EventModel[]
     */
    private function matchModels($events)
    {
        /**
         * @var EventModel[]
         */
        $models = [];
        $triggerModels = [];
        $elementModels = [];

        foreach ($events as $event) {
            if (!isset($models[$event->id])) {
                $models[$event->id] = (new EventModel())
                    ->setId((int) $event->id)
                    ->setName($event->name)
                    ->setActive((int) $event->active)
                    ->setAsync((int) $event->async)
                    ->setModified(new DateTime($event->modified));
            }

            if (!isset($triggerModels[$event->triggerId])) {
                $triggerModel = (new Trigger())
                    ->setId((int) $event->triggerId)
                    ->setEvent($models[$event->id])
                    ->setMasterId((int) $event->triggerMasterId ?: null)
                    ->setModuleId((int) $event->triggerModuleId ?: null)
                    ->setWeekday((int) $event->triggerWeekday ?: null)
                    ->setDay((int) $event->triggerDay ?: null)
                    ->setMonth((int) $event->triggerMonth ?: null)
                    ->setYear((int) $event->triggerYear ?: null)
                    ->setHour((int) $event->triggerHour ?: null)
                    ->setMinute((int) $event->triggerMinute ?: null)
                    ->setPriority((int) $event->triggerPriority ?: null);
                $models[$event->id]->addTrigger($triggerModel);
                $triggerModels[$event->triggerId] = $triggerModel;
            }

            $elementModel = (new Element())
                ->setId((int) $event->elementId)
                ->setEvent($models[$event->id])
                ->setLeft((int) $event->elementLeft)
                ->setRight((int) $event->elementRight)
                ->setParent($event->elementParentId === null ? null : $elementModels[$event->elementParentId])
                ->setMasterId((int) $event->elementMasterId ?: null)
                ->setModuleId((int) $event->elementModuleId ?: null)
                ->setClass($event->elementClass)
                ->setMethod($event->elementMethod)
                ->setParams($event->elementParams)
                ->setCommand($event->elementCommand)
                ->setOperator($event->elementOperator)
                ->setValue($event->elementValue);
            $models[$event->id]->addElement($elementModel);
            $elementModels[$event->elementId] = $elementModel;
        }

        return $models;
    }
}
