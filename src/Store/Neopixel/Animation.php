<?php
namespace GibsonOS\Module\Hc\Store\Neopixel;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\Animation as AnimationService;

class Animation extends AbstractDatabaseStore
{
    /**
     * @return string
     */
    protected function getTableName(): string
    {
        return Sequence::getTableName();
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
        return [];
    }

    /**
     * @return array[]
     */
    public function getList(): array
    {
        $this->where[] = '`' . $this->getTableName() . '`.`type`=' . $this->database->escape(AnimationService::SEQUENCE_TYPE);

        $this->table->setWhere($this->getWhere());
        $this->table->setOrderBy('`' . $this->getTableName() . '`.`name` ASC');
        $this->table->select(false,
            '`' . $this->getTableName() . '`.`id`, ' .
            '`' . $this->getTableName() . '`.`name`'
        );

        $list = [];

        foreach ($this->table->connection->fetchObjectList() as $sequence) {
            $list[] = [
                'id' => $sequence->id,
                'name' => $sequence->name,
            ];
        }

        return $list;
    }

    /**
     * @param int $slaveId
     * @return Animation
     */
    public function setSlave(int $slaveId): Animation
    {
        if ($slaveId === 0) {
            unset($this->where['moduleId']);
        } else {
            $this->where['moduleId'] = '`' . $this->getTableName() . '`.`module_id`=' . $this->database->escape($slaveId);
        }

        return $this;
    }
}