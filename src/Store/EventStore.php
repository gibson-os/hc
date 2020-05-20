<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use Exception;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Event;

class EventStore extends AbstractDatabaseStore
{
    protected function getTableName(): string
    {
        return Event::getTableName();
    }

    protected function getCountField(): string
    {
        return '`' . $this->getTableName() . '`.`id`';
    }

    protected function getOrderMapping(): array
    {
        return [
            'name' => '`' . $this->getTableName() . '`.`name`',
            'master' => '`hc_master`.`name`',
            'module' => '`hc_module`.`name`',
            'trigger' => '`hc_event_trigger`.`trigger`',
        ];
    }

    /**
     * @throws Exception
     */
    public function getList(): array
    {
        $this->table->appendJoinLeft(
            '`gibson_os`.`hc_event_trigger`',
            '`hc_event_trigger`.`event_id`=`' . $this->getTableName() . '`.`id`'
        );
        $this->table->appendJoinLeft(
            '`gibson_os`.`hc_master`',
            '`hc_master`.`id`=`hc_event_trigger`.`master_id`'
        );
        $this->table->appendJoinLeft(
            '`gibson_os`.`hc_module`',
            '`hc_module`.`id`=`hc_event_trigger`.`module_id`'
        );

        $this->table->setWhere($this->getWhere());
        $this->table->setOrderBy($this->getOrderBy());
        $this->table->select(
            false,
            '`' . $this->getTableName() . '`.`id`, ' .
            '`' . $this->getTableName() . '`.`name`, ' .
            '`' . $this->getTableName() . '`.`active`, ' .
            '`' . $this->getTableName() . '`.`async`, ' .
            '`' . $this->getTableName() . '`.`modified`'
        );

        return $this->table->connection->fetchAssocList();
    }

    public function setMasterId(?int $masterId): EventStore
    {
        if (empty($masterId)) {
            unset($this->where['masterId']);
        } else {
            $this->where['masterId'] = '`hc_event_trigger`.`master_id`=' . $masterId;
        }

        return $this;
    }

    public function setSlaveId(?int $moduleId): EventStore
    {
        if (empty($moduleId)) {
            unset($this->where['moduleId']);
        } else {
            $this->where['moduleId'] = '`hc_event_trigger`.`module_id`=' . $moduleId;
        }

        return $this;
    }
}
