<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Store\AbstractDatabaseStore;

class SlaveStore extends AbstractDatabaseStore
{
    protected function getTableName(): string
    {
        return 'hc_module';
    }

    protected function getCountField(): string
    {
        return '`' . $this->getTableName() . '`.`id`';
    }

    /**
     * @return string[]
     */
    protected function getOrderMapping(): array
    {
        return [
            'name' => '`' . $this->getTableName() . '`.`name`',
            'type' => '`hy_type`.`name`',
            'address' => '`' . $this->getTableName() . '`.`address`',
            'offline' => '`' . $this->getTableName() . '`.`offline`',
            'added' => '`' . $this->getTableName() . '`.`added`',
            'modified' => '`' . $this->getTableName() . '`.`modified`',
        ];
    }

    /**
     * @return array[]
     */
    public function getList(): array
    {
        $this->table->appendJoinLeft(
            '`gibson_os`.`hc_type`',
            '`' . $this->getTableName() . '`.`type_id`=`hc_type`.`id`'
        );
        $this->table->setWhere($this->getWhere());
        $this->table->setOrderBy($this->getOrderBy());
        $this->table->select(
            false,
            '`' . $this->getTableName() . '`.`id`, '
            . '`' . $this->getTableName() . '`.`name`, '
            . '`' . $this->getTableName() . '`.`type_id`, '
            . '`' . $this->getTableName() . '`.`address`, '
            . '`' . $this->getTableName() . '`.`offline`, '
            . '`' . $this->getTableName() . '`.`added`, '
            . '`' . $this->getTableName() . '`.`modified`, '
            . '`hc_type`.`name` AS `type`,'
            . 'IFNULL(`' . $this->getTableName() . '`.`hertz`, `hc_type`.`hertz`) AS `hertz`,'
            . '`hc_type`.`ui_settings` AS `settings`,'
            . '`hc_type`.`helper`'
        );

        return $this->table->connection->fetchAssocList();
    }

    public function setMasterId(?int $masterId): SlaveStore
    {
        if ($masterId === null) {
            unset($this->where['masterId']);

            return $this;
        }

        $this->where['masterId'] = '`' . $this->getTableName() . '`.`master_id`=' . $masterId;

        return $this;
    }
}
