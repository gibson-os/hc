<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Neopixel;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\ImageService as ImageService;

class ImageStore extends AbstractDatabaseStore
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
        $this->where[] = '`' . $this->getTableName() . '`.`type`=' . $this->database->escape(ImageService::SEQUENCE_TYPE);

        $this->table->appendJoinLeft(
            '`gibson_os`.`' . Sequence\Element::getTableName() . '`',
            '`' . $this->getTableName() . '`.`id`=`' . Sequence\Element::getTableName() . '`.`sequence_id`'
        );

        $this->table->setWhere($this->getWhere());
        $this->table->setOrderBy('`' . $this->getTableName() . '`.`name` ASC');
        $this->table->select(
            false,
            '`' . $this->getTableName() . '`.`id`, ' .
            '`' . $this->getTableName() . '`.`name`, ' .
            '`' . Sequence\Element::getTableName() . '`.`data`'
        );

        $list = [];

        foreach ($this->table->connection->fetchObjectList() as $sequence) {
            $list[] = [
                'id' => $sequence->id,
                'name' => $sequence->name,
                'leds' => JsonUtility::decode($sequence->data),
            ];
        }

        return $list;
    }

    public function setSlave(int $slaveId): ImageStore
    {
        if ($slaveId === 0) {
            unset($this->where['moduleId']);
        } else {
            $this->where['moduleId'] = '`' . $this->getTableName() . '`.`module_id`=' . $this->database->escape($slaveId);
        }

        return $this;
    }
}
