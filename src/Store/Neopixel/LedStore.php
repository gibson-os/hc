<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Neopixel;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService as LedAttribute;

/**
 * Class Port.
 *
 * @package GibsonOS\Module\Hc\Store\Neopixel
 */
class LedStore extends AbstractDatabaseStore
{
    protected function getTableName(): string
    {
        return 'hc_attribute';
    }

    /**
     * @return array[]
     */
    public function getList(): array
    {
        $this->where[] = '`' . $this->getTableName() . '`.`type`=' . $this->database->escape(LedAttribute::ATTRIBUTE_TYPE);

        $this->table->appendJoinLeft(
            '`gibson_os`.`hc_attribute_value`',
            '`' . $this->getTableName() . '`.`id`=`hc_attribute_value`.`attribute_id`'
        );

        $this->table->setWhere($this->getWhere());
        $this->table->setOrderBy('`' . $this->getTableName() . '`.`sub_id` ASC');
        $this->table->select(
            false,
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
                    'id' => (int) $attribute->sub_id,
                ];
            }

            $list[$attribute->sub_id][$attribute->key] = (int) $attribute->value;
        }

        return $list;
    }

    public function getCountField(): string
    {
        return '`' . $this->getTableName() . '`.`sub_id`';
    }

    public function setModule(int $moduleId): LedStore
    {
        if ($moduleId === 0) {
            unset($this->where['moduleId']);
        } else {
            $this->where['moduleId'] = '`' . $this->getTableName() . '`.`module_id`=' . $moduleId;
        }

        return $this;
    }

    /**
     * @return string[]
     */
    protected function getOrderMapping(): array
    {
        return [];
    }
}
