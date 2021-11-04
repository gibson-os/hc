<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value;

abstract class AbstractAttributeStore extends AbstractDatabaseStore
{
    protected ?int $moduleId = null;

    abstract protected function getType(): string;

    protected function getModelClassName(): string
    {
        return Attribute::class;
    }

    protected function setWheres(): void
    {
        $this->addWhere('`hc_attribute`.`type`=?', [$this->getType()]);

        if ($this->moduleId !== null) {
            $this->addWhere('`hc_attribute`.`module_id`=?', [$this->moduleId]);
        }
    }

    protected function initTable(): void
    {
        parent::initTable();

        $this->table->appendJoinLeft(
            '`' . Value::getTableName() . '`',
            '`' . $this->getTableName() . '`.`id`=`' . Value::getTableName() . '`.`attribute_id`'
        );
    }

    public function setModuleId(?int $moduleId): AbstractAttributeStore
    {
        $this->moduleId = $moduleId;

        return $this;
    }
}
