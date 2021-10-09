<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Ssd1306;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Dto\Ssd1306\Pixel;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Store\Neopixel\LedStore;

class PixelStore extends AbstractDatabaseStore
{
    protected function getTableName(): string
    {
        return Attribute::getTableName();
    }

    protected function getCountField(): string
    {
        return '`' . $this->getTableName() . '`.`sub_id`';
    }

    protected function getOrderMapping(): array
    {
        return [];
    }

    public function setModule(int $moduleId): PixelStore
    {
        if ($moduleId === 0) {
            unset($this->where['moduleId']);
        } else {
            $this->where['moduleId'] = '`' . $this->getTableName() . '`.`module_id`=' . $moduleId;
        }

        return $this;
    }
    
    public function getList(): iterable
    {
        $list = [];

        for ($page = 0; $page < 8; $page++) {
            for ($column = 0; $column < 128; $column++) {
                for ($bit = 0; $bit < 8; $bit++) {
                    $list[] = new Pixel($page, $column, $bit);
                }
            }
        }

        return $list;

//        $this->where[] = '`' . $this->getTableName() . '`.`type`=' . $this->database->escape(LedAttribute::ATTRIBUTE_TYPE);
//
//        $this->table->appendJoinLeft(
//            '`gibson_os`.`hc_attribute_value`',
//            '`' . $this->getTableName() . '`.`id`=`hc_attribute_value`.`attribute_id`'
//        );
//
//        $this->table->setWhere($this->getWhere());
//        $this->table->setOrderBy('`' . $this->getTableName() . '`.`sub_id` ASC');
//        $this->table->select(
//            false,
//            '`' . $this->getTableName() . '`.`id`, ' .
//            '`' . $this->getTableName() . '`.`sub_id`, ' .
//            '`' . $this->getTableName() . '`.`key`, ' .
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