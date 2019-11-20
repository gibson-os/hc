<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Store\AbstractDatabaseStore;

class MasterStore extends AbstractDatabaseStore
{
    /**
     * @return string
     */
    protected function getTableName(): string
    {
        return 'hc_master';
    }

    /**
     * @return string
     */
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
            'protocol' => '`' . $this->getTableName() . '`.`protocol`',
            'address' => '`' . $this->getTableName() . '`.`address`',
            'added' => '`' . $this->getTableName() . '`.`added`',
            'modified' => '`' . $this->getTableName() . '`.`modified`',
        ];
    }

    /**
     * @return array[]
     */
    public function getList(): array
    {
        $this->table->setOrderBy($this->getOrderBy());
        $this->table->select(false);

        return $this->table->connection->fetchAssocList();
    }
}
