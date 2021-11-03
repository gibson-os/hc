<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Neopixel;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Dto\Neopixel\Led;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService as LedAttribute;

class LedStore extends AbstractDatabaseStore
{
    private ?int $slaveId = null;

    protected function getModelClassName(): string
    {
        return Attribute::class;
    }

    protected function setWheres(): void
    {
        $this->addWhere('`hc_attribute`.`type`=?', [LedAttribute::ATTRIBUTE_TYPE]);
    }

    /**
     * @return array<int, Led>
     */
    public function getList(): array
    {
        $this->table
            ->appendJoinLeft(
                '`gibson_os`.`hc_attribute_value`',
                '`hc_attribute`.`id`=`hc_attribute_value`.`attribute_id`'
            )
            ->setWhere($this->getWhereString())
            ->setWhereParameters($this->getWhereParameters())
            ->setOrderBy('`hc_attribute`.`sub_id` ASC')
        ;

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

    public function getCountField(): string
    {
        return '`hc_attribute`.`sub_id`';
    }

    public function setSlaveId(?int $slaveId): LedStore
    {
        $this->slaveId = $slaveId;

        return $this;
    }
}
