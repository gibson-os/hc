<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Sequence;

abstract class AbstractSequenceStore extends AbstractDatabaseStore
{
    protected ?int $moduleId = null;

    abstract protected function getType(): int;

    protected function getModelClassName(): string
    {
        return Sequence::class;
    }

    protected function setWheres(): void
    {
        $tableName = $this->getTableName();
        $this->addWhere('`' . $tableName . '`.`type`=?', [$this->getType()]);

        if ($this->moduleId !== null) {
            $this->addWhere('`' . $tableName . '`.`module_id`=?', [$this->moduleId]);
        }
    }

    protected function initTable(): void
    {
        parent::initTable();

        if ($this->loadElements()) {
            $this->table->appendJoinLeft(
                '`' . Sequence\Element::getTableName() . '`',
                '`' . $this->getTableName() . '`.`id`=`' . Sequence\Element::getTableName() . '`.`sequence_id`'
            );
        }
    }

    public function setModuleId(?int $moduleId): AbstractSequenceStore
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    protected function loadElements(): bool
    {
        return true;
    }
}
