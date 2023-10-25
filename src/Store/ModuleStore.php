<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use Generator;
use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Core\Wrapper\DatabaseStoreWrapper;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use MDO\Dto\Query\Join;
use MDO\Enum\OrderDirection;
use MDO\Exception\ClientException;

/**
 * @extends AbstractDatabaseStore<Module>
 */
class ModuleStore extends AbstractDatabaseStore
{
    private ?int $masterId = null;

    public function __construct(
        DatabaseStoreWrapper $databaseStoreWrapper,
        #[GetTableName(Type::class)]
        private readonly string $typeTableName,
    ) {
        parent::__construct($databaseStoreWrapper);
    }

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
            $this->addWhere('`m`.`master_id`=?', [$this->masterId]);
        }
    }

    public function initQuery(): void
    {
        parent::initQuery();

        $this->selectQuery
            ->addJoin(new Join($this->getTable($this->typeTableName), 't', '`m`.`type_id=`t`.`id`'))
            ->setSelect('IFNULL(`hc_module`.`hertz`, `hc_type`.`hertz`)', 'hertz')
            ->setSelect('`hc_type`.`name`', 'type')
            ->setSelect('`hc_type`.`ui_settings`', 'settings')
            ->setSelect('`hc_type`.`helper`', 'helper')
        ;
    }

    /**
     * @throws ClientException
     */
    public function getModels(): Generator
    {
        $result = $this->getDatabaseStoreWrapper()->getClient()->execute($this->selectQuery);

        foreach ($result->iterateRecords() as $record) {
            yield $record->getValuesAsArray();
        }
    }

    public function setMasterId(?int $masterId): ModuleStore
    {
        $this->masterId = $masterId;

        return $this;
    }
}
