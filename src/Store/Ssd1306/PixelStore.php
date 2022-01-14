<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Ssd1306;

use GibsonOS\Module\Hc\Dto\Ssd1306\Pixel;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Store\AbstractAttributeStore;

class PixelStore extends AbstractAttributeStore
{
    protected function getType(): string
    {
        return '';
    }

    protected function getCountField(): string
    {
        return '`' . $this->tableName . '`.`sub_id`';
    }

    public function getList(): iterable
    {
        for ($page = 0; $page < 8; ++$page) {
            for ($column = 0; $column < 128; ++$column) {
                for ($bit = 0; $bit < 8; ++$bit) {
                    yield new Pixel($page, $column, $bit);
                }
            }
        }

//        $this->where[] = '`hc_attribute`.`type`=' . $this->database->escape(LedAttribute::ATTRIBUTE_TYPE);
//
//        $this->table->appendJoinLeft(
//            '`gibson_os`.`hc_attribute_value`',
//            '`hc_attribute`.`id`=`hc_attribute_value`.`attribute_id`'
//        );
//
//        $this->table->setWhere($this->getWhereString());
//        $this->table->setWhereParameters($this->getWhereParameters());
//        $this->table->setOrderBy('`hc_attribute`.`sub_id` ASC');
//        $this->table->selectPrepared(
//            false,
//            '`hc_attribute`.`id`, ' .
//            '`hc_attribute`.`sub_id`, ' .
//            '`hc_attribute`.`key`, ' .
//            '`hc_attribute_value`.`order`, ' .
//            '`hc_attribute_value`.`value`'
//        );
//
//        $list = [];
//
//        foreach ($this->table->connection->fetchObjectList() as $attribute) {
//            $number = (int) $attribute->sub_id;
//
//            if (!isset($list[$number])) {
//                $list[$number] = (new Led())->setNumber($number);
//            }
//
//            $list[$number]->{'set' . ucfirst($attribute->key)}((int) $attribute->value);
//        }
//
//        return $list;
    }
}
