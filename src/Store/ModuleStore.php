<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use mysqlDatabase;

class ModuleStore extends AbstractDatabaseStore
{
    private ?int $masterId = null;

    public function __construct(
        #[GetTableName(Type::class)] private string $typeTableName,
        mysqlDatabase $database = null
    ) {
        parent::__construct($database);
    }

    protected function getModelClassName(): string
    {
        return Module::class;
    }

    protected function getCountField(): string
    {
        return '`hc_module`.`id`';
    }

    protected function getDefaultOrder(): string
    {
        return '`hc_module`.`address`';
    }

    /**
     * @return string[]
     */
    protected function getOrderMapping(): array
    {
        return [
            'name' => '`hc_module`.`name`',
            'type' => '`hy_type`.`name`',
            'address' => '`hc_module`.`address`',
            'offline' => '`hc_module`.`offline`',
            'added' => '`hc_module`.`added`',
            'modified' => '`hc_module`.`modified`',
        ];
    }

    protected function setWheres(): void
    {
        if ($this->masterId !== null) {
            $this->addWhere('`hc_module`.`master_id`=?', [$this->masterId]);
        }
    }

    public function initTable(): void
    {
        parent::initTable();

        $this->table->appendJoinLeft(
            $this->typeTableName,
            '`' . $this->tableName . '`.`type_id`=`' . $this->typeTableName . '`.`id`'
        );
    }

    public function getList(): array
    {
        $this->initTable();
        $this->table->selectPrepared(
            false,
            '`hc_module`.`id`, '
            . '`hc_module`.`name`, '
            . '`hc_module`.`type_id`, '
            . '`hc_module`.`address`, '
            . '`hc_module`.`offline`, '
            . '`hc_module`.`added`, '
            . '`hc_module`.`modified`, '
            . '`hc_type`.`name` AS `type`,'
            . 'IFNULL(`hc_module`.`hertz`, `hc_type`.`hertz`) AS `hertz`,'
            . '`hc_type`.`ui_settings` AS `settings`,'
            . '`hc_type`.`helper`'
        );

        return $this->table->connection->fetchAssocList();
    }

    public function setMasterId(?int $masterId): ModuleStore
    {
        $this->masterId = $masterId;

        return $this;
    }
}
