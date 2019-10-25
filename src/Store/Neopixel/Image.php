<?php
namespace GibsonOS\Module\Hc\Store\Neopixel;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Core\Utility\Json;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\Image as ImageService;

class Image extends AbstractDatabaseStore
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
        $this->where[] = '`' . $this->getTableName() . '`.`type`=' . $this->database->escape(ImageService::SEQUENCE_TYPE);

        $this->table->appendJoinLeft(
            '`gibson_os`.`' . Sequence\Element::getTableName() . '`',
            '`' . $this->getTableName() . '`.`id`=`' . Sequence\Element::getTableName() . '`.`sequence_id`'
        );

        $this->table->setWhere($this->getWhere());
        $this->table->setOrderBy('`' . $this->getTableName() . '`.`name` ASC');
        $this->table->select(false,
            '`' . $this->getTableName() . '`.`id`, ' .
            '`' . $this->getTableName() . '`.`name`, ' .
            '`' . Sequence\Element::getTableName() . '`.`data`'
        );

        $list = [];

        foreach ($this->table->connection->fetchObjectList() as $sequence) {
            $list[] = [
                'id' => $sequence->id,
                'name' => $sequence->name,
                'leds' => Json::decode($sequence->data),
            ];
        }

        return $list;
    }

    /**
     * @param int $slaveId
     * @return Image
     */
    public function setSlave(int $slaveId): Image
    {
        if ($slaveId === 0) {
            unset($this->where['moduleId']);
        } else {
            $this->where['moduleId'] = '`' . $this->getTableName() . '`.`module_id`=' . $this->database->escape($slaveId);
        }

        return $this;
    }
}