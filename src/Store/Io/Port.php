<?php
namespace GibsonOS\Module\Hc\Store\Io;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Service\Slave\Io as IoService;

/**
 * Class Port
 * @package GibsonOS\Module\Hc\Store\Io
 */
class Port extends AbstractDatabaseStore
{
    /**
     * @return string
     */
    protected function getTableName()
    {
        return 'hc_attribute';
    }

    /**
     * @return array[]
     */
    public function getList()
    {
        $this->where[] = '`' . $this->getTableName() . '`.`type`=' . $this->database->escape(IoService::ATTRIBUTE_TYPE_PORT);

        $this->table->appendJoinLeft(
            '`gibson_os`.`hc_attribute_value`',
            '`' . $this->getTableName() . '`.`id`=`hc_attribute_value`.`attribute_id`'
        );

        $this->table->setWhere($this->getWhere());
        $this->table->setOrderBy('`' . $this->getTableName() . '`.`sub_id` ASC');
        $this->table->select(false,
            '`' . $this->getTableName() . '`.`id`, ' .
            '`' . $this->getTableName() . '`.`sub_id`, ' .
            '`' . $this->getTableName() . '`.`key`, ' .
            '`hc_attribute_value`.`order`, ' .
            '`hc_attribute_value`.`value`'
        );

        $list = [];

        foreach ($this->table->connection->fetchObjectList() as $attribute) {
            if (!isset($list[$attribute->sub_id])) {
                $list[$attribute->sub_id] = [
                    'number' => $attribute->sub_id
                ];
            }

            if ($attribute->key === IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES) {
                if (!isset($list[$attribute->sub_id][$attribute->key])) {
                    $list[$attribute->sub_id][$attribute->key] = [];
                }

                $list[$attribute->sub_id][$attribute->key][$attribute->order] = $attribute->value;
            } else {
                $list[$attribute->sub_id][$attribute->key] = $attribute->value;
            }
        }

        return $list;
    }

    /**
     * @return string
     */
    public function getCountField()
    {
        return '`' . $this->getTableName() . '`.`sub_id`';
    }

    /**
     * @param int $moduleId
     * @return Port
     */
    public function setModule($moduleId): Port
    {
        if ($moduleId === 0) {
            unset($this->where['moduleId']);
        } else {
            $this->where['moduleId'] = '`' . $this->getTableName() . '`.`module_id`=' . $this->database->escape($moduleId);
        }

        return $this;
    }

    /**
     * @return string[]
     */
    protected function getOrderMapping()
    {
        return [];
    }
}