<?php
namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Store\AbstractDatabaseStore;

class Master extends AbstractDatabaseStore
{
    /**
     * @return string
     */
    protected function getTableName()
    {
        return 'hc_master';
    }

    /**
     * @return string
     */
    protected function getCountField()
    {
        return '`' . $this->getTableName() . '`.`id`';
    }

    /**
     * @return string[]
     */
    protected function getOrderMapping()
    {
        return [
            'name' => '`' . $this->getTableName() . '`.`name`',
            'protocol' => '`' . $this->getTableName() . '`.`protocol`',
            'address' => '`' . $this->getTableName() . '`.`address`',
            'added' => '`' . $this->getTableName() . '`.`added`',
            'modified' => '`' . $this->getTableName() . '`.`modified`',
        ];
    }

    /**
     * @return array[]
     */
    public function getList()
    {
        $this->table->setOrderBy($this->getOrderBy());
        $this->table->select(false);

        return $this->table->connection->fetchAssocList();
    }
}