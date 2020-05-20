<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Master;

class MasterStore extends AbstractDatabaseStore
{
    protected function getTableName(): string
    {
        return Master::getTableName();
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
