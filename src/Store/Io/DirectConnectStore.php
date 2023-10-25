<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Io;

use Generator;
use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Core\Wrapper\DatabaseStoreWrapper;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use MDO\Dto\Query\Join;
use MDO\Dto\Select;
use MDO\Enum\JoinType;
use MDO\Enum\OrderDirection;

/**
 * @extends AbstractDatabaseStore<Port>
 *
 * @method Generator<Port> getList()
 */
class DirectConnectStore extends AbstractDatabaseStore
{
    protected Module $module;

    public function __construct(
        DatabaseStoreWrapper $databaseStoreWrapper,
        #[GetTableName(DirectConnect::class)]
        private readonly string $directConnectTableName,
    ) {
        parent::__construct($databaseStoreWrapper);
    }

    public function setModule(Module $module): void
    {
        $this->module = $module;
    }

    protected function getAlias(): ?string
    {
        return 'p';
    }

    protected function initQuery(): void
    {
        parent::initQuery();

        $directConnectTable = $this->getTable($this->directConnectTableName);
        $this->selectQuery
            ->addJoin(new Join(
                $directConnectTable,
                'dc',
                '`p`=`id`=`dc`.`input_port_id`',
                JoinType::LEFT,
            ))
            ->addJoin(new Join(
                $this->table,
                'op',
                '`op`=`id`=`dc`.`output_port_id`',
                JoinType::LEFT,
            ))
            ->setSelects(
                $this->getDatabaseStoreWrapper()->getSelectService()->getSelects([
                    new Select($this->table, 'p', 'port_'),
                    new Select($directConnectTable, 'dc', 'direct_connect_'),
                    new Select($this->table, 'op', 'output_port_'),
                ])
            )
        ;
    }

    protected function getModelClassName(): string
    {
        return Port::class;
    }

    protected function setWheres(): void
    {
        $this->addWhere('`p`.`module_id`=?', [$this->module->getId()]);
    }

    protected function getDefaultOrder(): array
    {
        return [
            '`p`.`number`' => OrderDirection::ASC,
            '`dc`.`order`' => OrderDirection::ASC,
        ];
    }
}
