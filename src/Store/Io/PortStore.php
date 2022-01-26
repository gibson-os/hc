<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Io;

use GibsonOS\Module\Hc\Service\Slave\IoService as IoService;
use GibsonOS\Module\Hc\Store\AbstractAttributeStore;

class PortStore extends AbstractAttributeStore
{
    protected function getType(): string
    {
        return IoService::ATTRIBUTE_TYPE_PORT;
    }

    protected function getTypeName(): string
    {
        return 'ssd1306';
    }

    /**
     * @return array[]
     */
    public function getList(): array
    {
        $this->initTable();
        $this->table->setOrderBy('`hc_attribute`.`sub_id` ASC, `hc_attribute_value`.`order`');

        $this->table->selectPrepared(
            false,
            '`hc_attribute`.`id`, ' .
            '`hc_attribute`.`sub_id`, ' .
            '`hc_attribute`.`key`, ' .
            '`hc_attribute_value`.`order`, ' .
            '`hc_attribute_value`.`value`'
        );

        $list = [];

        foreach ($this->table->connection->fetchObjectList() as $attribute) {
            if (!isset($list[$attribute->sub_id])) {
                $list[$attribute->sub_id] = [
                    'number' => $attribute->sub_id,
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
}
