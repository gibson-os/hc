<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Type;

class TypeStore extends AbstractDatabaseStore
{
    protected function getTableName(): string
    {
        return Type::class;
    }

    protected function getCountField(): string
    {
        return '`' . $this->getTableName() . '`.`id`';
    }

    protected function getOrderMapping(): array
    {
        return [
            'name' => 'name',
            'helper' => 'helper',
            'network' => 'network',
            'hertz' => 'hertz',
            'isHcSlave' => 'isHcSlave',
        ];
    }

    public function getList(): array
    {
        $this->table->setLimit($this->getRows(), $this->getFrom());
        $this->table->select(false);

        return $this->table->connection->fetchAssocList();
    }
}
