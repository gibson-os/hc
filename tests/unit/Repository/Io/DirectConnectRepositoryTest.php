<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Repository\Io;

use Codeception\Test\Unit;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Repository\Io\DirectConnectRepository;
use GibsonOS\Test\Unit\Core\Repository\RepositoryTrait;
use MDO\Dto\Query\Where;
use MDO\Dto\Result;
use MDO\Dto\Value;
use MDO\Enum\ValueType;
use MDO\Query\DeleteQuery;
use MDO\Query\SelectQuery;
use MDO\Query\UpdateQuery;

class DirectConnectRepositoryTest extends Unit
{
    use RepositoryTrait;

    private DirectConnectRepository $directConnectRepository;

    protected function _before(): void
    {
        $this->loadRepository('hc_io_direct_connect');

        $this->directConnectRepository = new DirectConnectRepository(
            $this->repositoryWrapper->reveal(),
            'hc_io_direct_connect',
        );
    }

    public function testUpdateOrder(): void
    {
        $directConnect = (new DirectConnect($this->modelWrapper->reveal()))
            ->setOrder(42)
            ->setInputPortId(24)
        ;
        $updateQuery = (new UpdateQuery($this->table, ['order' => new Value('`order`-1', ValueType::FUNCTION)]))
            ->addWhere(new Where('`input_port_id`=?', [24]))
            ->addWhere(new Where('`order`>?', [42]))
        ;
        $this->tableManager->getTable('hc_io_direct_connect')
            ->shouldBeCalledOnce()
            ->willReturn($this->table)
        ;
        $this->repositoryWrapper->getTableManager()
            ->shouldBeCalledOnce()
            ->willReturn($this->tableManager->reveal())
        ;
        $this->repositoryWrapper->getClient()
            ->shouldBeCalledOnce()
            ->willReturn($this->client->reveal())
        ;
        $this->client->execute($updateQuery)
            ->shouldBeCalledOnce()
            ->willReturn(new Result(null))
        ;

        $this->directConnectRepository->updateOrder($directConnect);
    }

    public function testGetByOrder(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`input_port_id`=? AND `order`=?', [42, 24]))
            ->setLimit(1)
        ;

        $this->assertEquals(
            $this->loadModel($selectQuery, DirectConnect::class),
            $this->directConnectRepository->getByOrder((new Port($this->modelWrapper->reveal()))->setId(42), 24),
        );
    }

    public function testDeleteByInputport(): void
    {
        $deleteQuery = (new DeleteQuery($this->table))
            ->addWhere(new Where('`input_port_id`=?', [42]))
        ;

        $this->loadDeleteQuery($deleteQuery);

        $this->directConnectRepository->deleteByInputPort((new Port($this->modelWrapper->reveal()))->setId(42));
    }
}
