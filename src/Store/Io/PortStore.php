<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Io;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Service\Slave\IoService as IoService;

class PortStore extends AbstractDatabaseStore
{
    private ?int $moduleId = null;

    protected function getModelClassName(): string
    {
        return Attribute::class;
    }

    protected function setWheres(): void
    {
        $this->addWhere('`hc_attribute`.`type`=?', [IoService::ATTRIBUTE_TYPE_PORT]);

        if ($this->moduleId !== null) {
            $this->addWhere('`hc_attribute`.`module_id`=?', [$this->moduleId]);
        }
    }

    protected function initTable(): void
    {
        parent::initTable();

        $this->table->appendJoinLeft(
            '`gibson_os`.`hc_attribute_value`',
            '`hc_attribute`.`id`=`hc_attribute_value`.`attribute_id`'
        );
    }

    /**
     * @return array[]
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

    public function setModuleId(?int $moduleId): PortStore
    {
        $this->moduleId = $moduleId;

        return $this;
    }
}
