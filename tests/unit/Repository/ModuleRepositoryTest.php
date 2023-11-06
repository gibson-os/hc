<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Repository;

use Codeception\Test\Unit;
use DateTimeImmutable;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Test\Unit\Core\Repository\RepositoryTrait;
use MDO\Dto\Query\Where;
use MDO\Query\SelectQuery;
use Psr\Log\LoggerInterface;

class ModuleRepositoryTest extends Unit
{
    use RepositoryTrait;

    private ModuleRepository $moduleRepository;

    protected function _before()
    {
        $this->loadRepository('hc_module');

        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->moduleRepository = new ModuleRepository(
            $this->repositoryWrapper->reveal(),
            $this->logger->reveal(),
            'hc_module',
        );
    }

    public function testGetById(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`id`=?', [42]))
            ->setLimit(1)
        ;

        $model = $this->loadModel($selectQuery, Module::class);
        $module = $this->moduleRepository->getById(42);
        $date = new DateTimeImmutable();
        $model->setAdded($date)->setModified($date);
        $module->setAdded($date)->setModified($date);

        $this->assertEquals($model, $module);
    }

    public function testGetByDeviceId(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`device_id`=?', [42]))
            ->setLimit(1)
        ;

        $model = $this->loadModel($selectQuery, Module::class);
        $module = $this->moduleRepository->getByDeviceId(42);
        $date = new DateTimeImmutable();
        $model->setAdded($date)->setModified($date);
        $module->setAdded($date)->setModified($date);

        $this->assertEquals($model, $module);
    }

    public function testFindByName(): void
    {
        $selectQuery = (new SelectQuery($this->table))
            ->addWhere(new Where('`name` LIKE ?', ['marvin%']))
        ;

        $model = $this->loadModel($selectQuery, Module::class, '');
        $module = $this->moduleRepository->findByName('marvin')[0];
        $date = new DateTimeImmutable();
        $model->setAdded($date)->setModified($date);
        $module->setAdded($date)->setModified($date);

        $this->assertEquals($model, $module);
    }

    public function testFindByNameWithType(): void
    {
        $selectQuery = (new SelectQuery($this->table))
            ->addWhere(new Where('`name` LIKE ? AND `type_id`=?', ['marvin%', 42]))
        ;

        $model = $this->loadModel($selectQuery, Module::class, '');
        $module = $this->moduleRepository->findByName('marvin', 42)[0];
        $date = new DateTimeImmutable();
        $model->setAdded($date)->setModified($date);
        $module->setAdded($date)->setModified($date);

        $this->assertEquals($model, $module);
    }

    public function testGetByAddress(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`address`=? AND `master_id`=?', [42, 24]))
            ->setLimit(1)
        ;

        $model = $this->loadModel($selectQuery, Module::class);
        $module = $this->moduleRepository->getByAddress(42, 24);
        $date = new DateTimeImmutable();
        $model->setAdded($date)->setModified($date);
        $module->setAdded($date)->setModified($date);

        $this->assertEquals($model, $module);
    }
}
