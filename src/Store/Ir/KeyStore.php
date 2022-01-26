<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Ir;

use Generator;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Service\Slave\IrService;
use GibsonOS\Module\Hc\Store\AbstractAttributeStore;

class KeyStore extends AbstractAttributeStore
{
    protected function getType(): string
    {
        return IrService::ATTRIBUTE_TYPE_KEY;
    }

    protected function getTypeName(): string
    {
        return 'ir';
    }

    protected function getCountField(): string
    {
        return '`' . $this->tableName . '`.`sub_id`';
    }

    /**
     * @return Generator<Key>
     */
    public function getList(): iterable
    {
        $this->initTable();
        $this->table->setOrderBy('`' . $this->tableName . '`.`sub_id` ASC');

        $this->table->selectPrepared(
            false,
            '`hc_attribute`.`id`, ' .
            '`hc_attribute`.`sub_id`, ' .
            '`hc_attribute`.`key`, ' .
            '`hc_attribute_value`.`order`, ' .
            '`hc_attribute_value`.`value`'
        );
    }
}
