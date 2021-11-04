<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Neopixel;

use GibsonOS\Module\Hc\Dto\Neopixel\Led;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService as LedAttribute;
use GibsonOS\Module\Hc\Store\AbstractAttributeStore;

class LedStore extends AbstractAttributeStore
{
    protected function getType(): string
    {
        return LedAttribute::ATTRIBUTE_TYPE;
    }

    /**
     * @return array<int, Led>
     */
    public function getList(): array
    {
        $this->initTable();
        $this->table->setOrderBy('`hc_attribute`.`sub_id` ASC');

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
            $number = (int) $attribute->sub_id;

            if (!isset($list[$number])) {
                $list[$number] = (new Led())->setNumber($number);
            }

            $list[$number]->{'set' . ucfirst($attribute->key)}((int) $attribute->value);
        }

        return $list;
    }
}
