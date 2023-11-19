<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Repository;

use Codeception\Test\Unit;
use DateTimeImmutable;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Test\Unit\Core\Repository\RepositoryTrait;
use MDO\Dto\Query\Where;
use MDO\Query\SelectQuery;

class MasterRepositoryTest extends Unit
{
    use RepositoryTrait;

    private MasterRepository $masterRepository;

    protected function _before(): void
    {
        $this->loadRepository('hc_master');

        $this->masterRepository = new MasterRepository(
            $this->repositoryWrapper->reveal(),
            'hc_type_default_address',
            'hc_module',
        );
    }

    public function testGetById(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`id`=?', [42]))
            ->setLimit(1)
        ;

        $model = $this->loadModel($selectQuery, Master::class);
        $master = $this->masterRepository->getById(42);
        $date = new DateTimeImmutable();
        $model->setAdded($date)->setModified($date);
        $master->setAdded($date)->setModified($date);

        $this->assertEquals($model, $master);
    }

    public function testGetByAddress(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`protocol`=? AND `address`=?', ['galaxy', '42.42.42.42']))
            ->setLimit(1)
        ;

        $model = $this->loadModel($selectQuery, Master::class);
        $master = $this->masterRepository->getByAddress('42.42.42.42', 'galaxy');
        $date = new DateTimeImmutable();
        $model->setAdded($date)->setModified($date);
        $master->setAdded($date)->setModified($date);

        $this->assertEquals($model, $master);
    }

    public function testGetByName(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`protocol`=? AND `name`=?', ['galaxy', 'marvin']))
            ->setLimit(1)
        ;

        $model = $this->loadModel($selectQuery, Master::class);
        $master = $this->masterRepository->getByName('marvin', 'galaxy');
        $date = new DateTimeImmutable();
        $model->setAdded($date)->setModified($date);
        $master->setAdded($date)->setModified($date);

        $this->assertEquals($model, $master);
    }

    public function testFindByName(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`name` LIKE ?', ['marvin%']))
        ;

        $model = $this->loadModel($selectQuery, Master::class);
        $master = $this->masterRepository->findByName('marvin')[0];
        $date = new DateTimeImmutable();
        $model->setAdded($date)->setModified($date);
        $master->setAdded($date)->setModified($date);

        $this->assertEquals($model, $master);
    }
}
