<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Repository\Io;

use Codeception\Test\Unit;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Io\PortRepository;
use GibsonOS\Test\Unit\Core\Repository\RepositoryTrait;
use MDO\Dto\Query\Where;
use MDO\Query\SelectQuery;
use MDO\Service\SelectService;

class PortRepositoryTest extends Unit
{
    use RepositoryTrait;

    private PortRepository $portRepository;

    protected function _before()
    {
        $this->loadRepository('hc_io_port');

        $this->portRepository = new PortRepository($this->repositoryWrapper->reveal());
    }

    public function testGetById(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`module_id`=? AND `id`=?', [42, 24]))
            ->setLimit(1)
        ;

        $this->assertEquals(
            $this->loadModel($selectQuery, Port::class),
            $this->portRepository->getById(42, 24),
        );
    }

    public function testGetByNumber(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`module_id`=? AND `number`=?', [42, 24]))
            ->setLimit(1)
        ;

        $this->assertEquals(
            $this->loadModel($selectQuery, Port::class),
            $this->portRepository->getByNumber((new Module($this->modelWrapper->reveal()))->setId(42), 24),
        );
    }

    public function testGetByModule(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`module_id`=?', [42]))
            ->setOrder('`number`')
        ;

        $this->assertEquals(
            $this->loadModel($selectQuery, Port::class),
            $this->portRepository->getByModule((new Module($this->modelWrapper->reveal()))->setId(42))[0],
        );
    }

    public function testFindByName(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`module_id`=? AND `name` REGEXP ?', [42, 'galaxy']))
        ;
        $selectService = $this->prophesize(SelectService::class);
        $selectService->getUnescapedRegexString('galaxy')
            ->shouldBeCalledOnce()
            ->willReturn('galaxy')
        ;
        $this->repositoryWrapper->getSelectService()
            ->shouldBeCalledOnce()
            ->willReturn($selectService->reveal())
        ;

        $this->assertEquals(
            $this->loadModel($selectQuery, Port::class),
            $this->portRepository->findByName(42, 'galaxy')[0],
        );
    }
}
