<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Ir;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Dto\Model\ChildrenMapping;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Core\Wrapper\DatabaseStoreWrapper;
use GibsonOS\Module\Hc\Model\Ir\Key;
use MDO\Dto\Query\Join;
use MDO\Dto\Select;
use MDO\Enum\OrderDirection;

/**
 * @extends AbstractDatabaseStore<Key>
 */
class KeyStore extends AbstractDatabaseStore
{
    public function __construct(
        DatabaseStoreWrapper $databaseStoreWrapper,
        #[GetTableName(Key\Name::class)]
        private readonly string $keyTableName,
    ) {
        parent::__construct($databaseStoreWrapper);
    }

    protected function getModelClassName(): string
    {
        return Key::class;
    }

    protected function getAlias(): ?string
    {
        return 'k';
    }

    protected function getDefaultOrder(): array
    {
        return ['`n`.`name`' => OrderDirection::ASC];
    }

    protected function getOrderExtension(): array
    {
        return ['`n`.`name`' => OrderDirection::ASC];
    }

    protected function initQuery(): void
    {
        parent::initQuery();

        $keyTable = $this->getTable($this->keyTableName);
        $this->selectQuery
            ->setSelects($this->getDatabaseStoreWrapper()->getSelectService()->getSelects([
                new Select($this->table, 'k', ''),
                new Select($keyTable, 'n', 'name_'),
            ]))
            ->addJoin(new Join($keyTable, 'n', '`k`.`id`=`n`.`key_id`'));
    }

    protected function getOrderMapping(): array
    {
        return [
            'name' => '`n`.`name`',
            'protocolName' => '`k`.`protocol`',
            'address' => '`k`.`address`',
            'command' => '`k`.`command`',
        ];
    }

    protected function getModels(): iterable
    {
        $result = $this->getDatabaseStoreWrapper()->getClient()->execute($this->selectQuery);

        foreach ($result->iterateRecords() as $record) {
            $model = $this->getModel($record);
            $this->getDatabaseStoreWrapper()->getChildrenMapper()->getChildrenModels(
                $record,
                $model,
                [new ChildrenMapping('names', 'name_', 'n')],
            );

            yield $model;
        }
    }
}
