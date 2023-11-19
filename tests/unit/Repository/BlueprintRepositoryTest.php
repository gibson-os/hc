<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Repository;

use Codeception\Test\Unit;
use GibsonOS\Core\Dto\Model\ChildrenMapping;
use GibsonOS\Module\Hc\Enum\Blueprint\Type;
use GibsonOS\Module\Hc\Model\Blueprint;
use GibsonOS\Module\Hc\Repository\BlueprintRepository;
use GibsonOS\Test\Unit\Core\Repository\RepositoryTrait;
use MDO\Dto\Query\Where;
use MDO\Dto\Result;
use MDO\Query\SelectQuery;
use MDO\Service\SelectService;

class BlueprintRepositoryTest extends Unit
{
    use RepositoryTrait;

    private BlueprintRepository $blueprintRepository;

    public function _before(): void
    {
        $this->loadRepository('hc_blueprint');

        $this->blueprintRepository = new BlueprintRepository($this->repositoryWrapper->reveal());
    }

    public function testGetById(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`id`=?', [42]))
            ->setLimit(1)
        ;

        $this->assertEquals(
            $this->loadModel($selectQuery, Blueprint::class),
            $this->blueprintRepository->getById(42),
        );
    }

    public function testFindByName(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`name` LIKE ?', ['galaxy%']))
        ;
        $this->assertEquals(
            $this->loadModel($selectQuery, Blueprint::class),
            $this->blueprintRepository->findByName('galaxy')[0],
        );
    }

    /**
     * @dataProvider getData
     */
    public function testGetExpanded(array $childrenTypes): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`t`.`id`=:blueprintId', ['blueprintId' => 42]))
        ;
        $this->childrenQuery->extend($selectQuery, Blueprint::class, [
            new ChildrenMapping('geometries', 'geometry_', 'g', [
                new ChildrenMapping('module', 'module_', 'm', [
                    new ChildrenMapping('type', 'type_', 'ty'),
                ]),
            ]),
        ])
            ->shouldBeCalledOnce()
            ->willReturn($selectQuery)
        ;

        $model = $this->loadModel($selectQuery, Blueprint::class)->setChildren([]);

        if (count($childrenTypes) > 0) {
            $parameters = $childrenTypes;
            $parameters[] = 42;
            $childrenSelectQuery = (new SelectQuery($this->table, 't'))
                ->addWhere(new Where(
                    '`t`.`type` IN (?) AND `t`.`parent_id`=?',
                    $parameters,
                ))
            ;
            $selectService = $this->prophesize(SelectService::class);
            $selectService->getParametersString($childrenTypes)
                ->shouldBeCalledOnce()
                ->willReturn('?')
            ;
            $this->repositoryWrapper->getSelectService()
                ->shouldBeCalledOnce()
                ->willReturn($selectService->reveal())
            ;
            $this->childrenQuery->extend($childrenSelectQuery, Blueprint::class, [
                new ChildrenMapping('geometries', 'geometry_', 'g', [
                    new ChildrenMapping('module', 'module_', 'm', [
                        new ChildrenMapping('type', 'type_', 'ty'),
                    ]),
                ]),
            ])
                ->shouldBeCalledOnce()
                ->willReturn($childrenSelectQuery)
            ;
            $this->client->execute($childrenSelectQuery)
                ->shouldBeCalledOnce()
                ->willReturn(new Result(null))
            ;
            $this->tableManager->getTable('hc_blueprint')
                ->shouldBeCalledTimes(2)
            ;
            $this->repositoryWrapper->getModelWrapper()
                ->shouldBeCalledTimes(4)
            ;
            $this->repositoryWrapper->getTableManager()
                ->shouldBeCalledTimes(2)
            ;
            $this->repositoryWrapper->getChildrenQuery()
                ->shouldBeCalledTimes(2)
            ;
            $this->repositoryWrapper->getClient()
                ->shouldBeCalledTimes(2)
            ;
            //            $this->loadModel($childrenSelectQuery, Blueprint::class);
        }

        $this->assertEquals(
            $model,
            $this->blueprintRepository->getExpanded(42, $childrenTypes),
        );
    }

    public function getData(): array
    {
        return [
            'simple' => [[]],
            'frame' => [[Type::FRAME->name]],
            'room' => [[Type::ROOM->name]],
            'furnishing' => [[Type::FURNISHING->name]],
            'module' => [[Type::MODULE->name]],
            'all' => [[Type::FRAME->name, Type::ROOM->name, Type::FURNISHING->name, Type::MODULE->name]],
        ];
    }
}
