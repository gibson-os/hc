<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Event;

use DateTime;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Event as EventModel;
use GibsonOS\Module\Hc\Model\Event\Element as ElementModel;
use GibsonOS\Module\Hc\Model\Event\Trigger as TriggerModel;
use mysqlTable;
use stdClass;

class TriggerRepository extends AbstractRepository
{
    /**
     * @return TriggerModel[]
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
     * @return TriggerModel[]
     */
    public function getByModuleId(int $moduleId): array
    {
        $table = $this->initializeTable();
        $table->setWhere('`hc_event_trigger`.`module_id`=' . $moduleId);

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
        $table = $this->getTable(ElementModel::getTableName());
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
     * @return TriggerModel[]
     */
    private function matchModels($events)
    {
        /**
         * @var TriggerModel[]
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
                $models[$event->triggerId] = (new TriggerModel())
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

            $elementModel = (new ElementModel())
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
