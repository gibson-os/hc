<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Repository;

use Codeception\Test\Unit;
use DateTimeImmutable;
use GibsonOS\Module\Hc\Enum\Direction;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Test\Unit\Core\Repository\RepositoryTrait;
use MDO\Dto\Query\Where;
use MDO\Enum\OrderDirection;
use MDO\Query\SelectQuery;

class LogRepositoryTest extends Unit
{
    use RepositoryTrait;

    private LogRepository $logRepository;

    protected function _before(): void
    {
        $this->loadRepository('hc_log');

        $this->logRepository = new LogRepository($this->repositoryWrapper->reveal());
    }

    public function testCreate(): void
    {
        $this->repositoryWrapper->getModelWrapper()
            ->shouldBeCalledOnce()
            ->willReturn($this->modelWrapper->reveal())
        ;
        $model = (new Log($this->modelWrapper->reveal()))
            ->setType(42)
            ->setRawData('marvin')
            ->setDirection(Direction::INPUT)
        ;
        $log = $this->logRepository->create(42, 'marvin', Direction::INPUT);
        $date = new DateTimeImmutable();
        $model->setAdded($date);
        $log->setAdded($date);

        $this->assertEquals($model, $log);
    }

    public function testGetById(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`id`=?', [42]))
            ->setLimit(1)
        ;

        $model = $this->loadModel($selectQuery, Log::class);
        $log = $this->logRepository->getById(42);
        $date = new DateTimeImmutable();
        $model->setAdded($date);
        $log->setAdded($date);

        $this->assertEquals($model, $log);
    }

    /**
     * @dataProvider getGetLastEntryByModuleData
     */
    public function testGetLastEntryByModuleId(
        string $where,
        array $parameters,
        int $command = null,
        int $type = null,
        Direction $direction = null,
    ): void {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where($where, $parameters))
            ->setLimit(1)
            ->setOrders(['`id`' => OrderDirection::DESC])
        ;

        $model = $this->loadModel($selectQuery, Log::class);
        $log = $this->logRepository->getLastEntryByModuleId(42, $command, $type, $direction);
        $date = new DateTimeImmutable();
        $model->setAdded($date);
        $log->setAdded($date);

        $this->assertEquals($model, $log);
    }

    public function getGetLastEntryByModuleData(): array
    {
        return [
            'simple' => [
                '`module_id`=:moduleId',
                ['moduleId' => 42],
            ],
            'command' => [
                '`module_id`=:moduleId AND `command`=:command',
                ['moduleId' => 42, 'command' => 24],
                24,
            ],
            'type' => [
                '`module_id`=:moduleId AND `type`=:type',
                ['moduleId' => 42, 'type' => 24],
                null,
                24,
            ],
            'direction' => [
                '`module_id`=:moduleId AND `direction`=:direction',
                ['moduleId' => 42, 'direction' => 'input'],
                null,
                null,
                Direction::INPUT,
            ],
            'all' => [
                '`module_id`=:moduleId AND `command`=:command AND `type`=:type AND `direction`=:direction',
                ['moduleId' => 42, 'command' => 24, 'type' => 420, 'direction' => 'output'],
                24,
                420,
                Direction::OUTPUT,
            ],
        ];
    }

    /**
     * @dataProvider getGetLastEntryByMasterData
     */
    public function testGetLastEntryByMasterId(
        string $where,
        array $parameters,
        int $command = null,
        int $type = null,
        Direction $direction = null,
    ): void {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where($where, $parameters))
            ->setLimit(1)
            ->setOrders(['`id`' => OrderDirection::DESC])
        ;

        $model = $this->loadModel($selectQuery, Log::class);
        $log = $this->logRepository->getLastEntryByMasterId(42, $command, $type, $direction);
        $date = new DateTimeImmutable();
        $model->setAdded($date);
        $log->setAdded($date);

        $this->assertEquals($model, $log);
    }

    public function getGetLastEntryByMasterData(): array
    {
        return [
            'simple' => [
                '`master_id`=:masterId',
                ['masterId' => 42],
            ],
            'command' => [
                '`master_id`=:masterId AND `command`=:command',
                ['masterId' => 42, 'command' => 24],
                24,
            ],
            'type' => [
                '`master_id`=:masterId AND `type`=:type',
                ['masterId' => 42, 'type' => 24],
                null,
                24,
            ],
            'direction' => [
                '`master_id`=:masterId AND `direction`=:direction',
                ['masterId' => 42, 'direction' => 'input'],
                null,
                null,
                Direction::INPUT,
            ],
            'all' => [
                '`master_id`=:masterId AND `command`=:command AND `type`=:type AND `direction`=:direction',
                ['masterId' => 42, 'command' => 24, 'type' => 420, 'direction' => 'output'],
                24,
                420,
                Direction::OUTPUT,
            ],
        ];
    }

    /**
     * @dataProvider getGetPreviousEntryByModuleData
     */
    public function testGetPreviousEntryByModuleId(
        string $where,
        array $parameters,
        int $command = null,
        int $type = null,
        Direction $direction = null,
    ): void {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where($where, $parameters))
            ->setLimit(1)
            ->setOrders(['`id`' => OrderDirection::DESC])
        ;

        $model = $this->loadModel($selectQuery, Log::class);
        $log = $this->logRepository->getPreviousEntryByModuleId(7, 42, $command, $type, $direction);
        $date = new DateTimeImmutable();
        $model->setAdded($date);
        $log->setAdded($date);

        $this->assertEquals($model, $log);
    }

    public function getGetPreviousEntryByModuleData(): array
    {
        return [
            'simple' => [
                '`id`<:id AND `module_id`=:moduleId',
                ['id' => 7, 'moduleId' => 42],
            ],
            'command' => [
                '`id`<:id AND `module_id`=:moduleId AND `command`=:command',
                ['id' => 7, 'moduleId' => 42, 'command' => 24],
                24,
            ],
            'type' => [
                '`id`<:id AND `module_id`=:moduleId AND `type`=:type',
                ['id' => 7, 'moduleId' => 42, 'type' => 24],
                null,
                24,
            ],
            'direction' => [
                '`id`<:id AND `module_id`=:moduleId AND `direction`=:direction',
                ['id' => 7, 'moduleId' => 42, 'direction' => 'input'],
                null,
                null,
                Direction::INPUT,
            ],
            'all' => [
                '`id`<:id AND `module_id`=:moduleId AND `command`=:command AND `type`=:type AND `direction`=:direction',
                ['id' => 7, 'moduleId' => 42, 'command' => 24, 'type' => 420, 'direction' => 'output'],
                24,
                420,
                Direction::OUTPUT,
            ],
        ];
    }
}
