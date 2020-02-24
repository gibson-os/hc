<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Neopixel;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\AnimationService as AnimationService;

class AnimationStore extends AbstractDatabaseStore
{
    protected function getTableName(): string
    {
        return Sequence::getTableName();
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
        return [];
    }

    /**
     * @return array[]
     */
    public function getList(): array
    {
        $this->where[] = '`' . $this->getTableName() . '`.`type`=' . AnimationService::SEQUENCE_TYPE;

        $this->table->setWhere($this->getWhere());
        $this->table->setOrderBy('`' . $this->getTableName() . '`.`name` ASC');
        $this->table->select(
            false,
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

    public function setSlave(int $slaveId): AnimationStore
    {
        if ($slaveId === 0) {
            unset($this->where['moduleId']);
        } else {
            $this->where['moduleId'] = '`' . $this->getTableName() . '`.`module_id`=' . $slaveId;
        }

        return $this;
    }
}
