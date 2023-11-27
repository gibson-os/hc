<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Dto\Model\ChildrenMapping;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Module;
use MDO\Enum\OrderDirection;

/**
 * @extends AbstractDatabaseStore<Module>
 */
class ModuleStore extends AbstractDatabaseStore
{
    private ?int $masterId = null;

    protected function getModelClassName(): string
    {
        return Module::class;
    }

    protected function getAlias(): ?string
    {
        return 'm';
    }

    protected function getCountField(): string
    {
        return '`m`.`id`';
    }

    protected function getDefaultOrder(): array
    {
        return ['`m`.`address`' => OrderDirection::ASC];
    }

    /**
     * @return string[]
     */
    protected function getOrderMapping(): array
    {
        return [
            'name' => '`m`.`name`',
            'type' => '`t`.`name`',
            'address' => '`m`.`address`',
            'offline' => '`m`.`offline`',
            'added' => '`m`.`added`',
            'modified' => '`m`.`modified`',
        ];
    }

    protected function setWheres(): void
    {
        if ($this->masterId !== null) {
            $this->addWhere('`m`.`master_id`=?', [$this->masterId]);
        }
    }

    protected function getExtends(): array
    {
        return [new ChildrenMapping('type', 'type_', 't')];
    }

    public function setMasterId(?int $masterId): ModuleStore
    {
        $this->masterId = $masterId;

        return $this;
    }
}
