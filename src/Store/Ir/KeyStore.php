<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Ir;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Ir\Key\Name;
use mysqlDatabase;

/**
 * @extends AbstractDatabaseStore<Key>
 */
class KeyStore extends AbstractDatabaseStore
{
    public function __construct(
        #[GetTableName(Name::class)]
        private readonly string $nameTableName,
        mysqlDatabase $database = null,
    ) {
        parent::__construct($database);
    }

    protected function getModelClassName(): string
    {
        return Key::class;
    }

    protected function getDefaultOrder(): string
    {
        return sprintf('`%s`.`name`', $this->nameTableName);
    }

    protected function initTable(): void
    {
        parent::initTable();

        $this->table
            ->appendJoin($this->nameTableName, sprintf(
                '`%s`.`id`=`%s`.`key_id`',
                $this->tableName,
                $this->nameTableName,
            ))
            ->setSelectString(sprintf(
                '`%s`.`id`, ' .
                '`%s`.`protocol`, ' .
                '`%s`.`address`, ' .
                '`%s`.`command`, ' .
                '`%s`.`id` `name_id`, ' .
                '`%s`.`name`',
                $this->tableName,
                $this->tableName,
                $this->tableName,
                $this->tableName,
                $this->nameTableName,
                $this->nameTableName,
            ))
        ;
    }

    protected function getModel(): Key
    {
        $record = $this->table->getSelectedRecord();
        /** @var Key $model */
        $model = parent::getModel();

        return $model->setNames([
            (new Name())
                ->setId((int) $record['name_id'])
                ->setName($record['name'])
                ->setKey($model),
        ]);
    }

    protected function getOrderMapping(): array
    {
        return [
            'name' => sprintf('`%s`.`name`', $this->nameTableName),
            'protocolName' => '`protocol`',
            'address' => '`address`',
            'command' => '`command`',
        ];
    }
}
