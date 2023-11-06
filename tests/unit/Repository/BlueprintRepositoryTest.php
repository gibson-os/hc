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
        $selectQuery = (new SelectQuery($this->table))
            ->addWhere(new Where('`name` LIKE ?', ['galaxy%']))
        ;
        $this->assertEquals(
            $this->loadModel($selectQuery, Blueprint::class, ''),
            $this->blueprintRepository->findByName('galaxy')[0],
        );
    }

    /**
     * @dataProvider getData
     */
    public function testGetExpanded(array $childrenTypes): void
    {
        $childrenWheres = [];

        if (count($childrenTypes)) {
            $childrenWheres[] = new Where('`c`.`type` IN (?)', $childrenTypes);
            $selectService = $this->prophesize(SelectService::class);
            $selectService->getParametersString($childrenTypes)
                ->shouldBeCalledOnce()
                ->willReturn('?')
            ;
            $this->repositoryWrapper->getSelectService()
                ->shouldBeCalledOnce()
                ->willReturn($selectService->reveal())
            ;
        }

        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`t`.`id`=:blueprintId', ['blueprintId' => 42]))
            ->setLimit(1)
        ;
        $this->childrenQuery->extend($selectQuery, Blueprint::class, [
            new ChildrenMapping('geometries', 'geometry_', 'g', [
                new ChildrenMapping('module', 'module_', 'm', [
                    new ChildrenMapping('type', 'type_', 'ty'),
                ]),
            ]),
            new ChildrenMapping('children', 'children_', 'c', [
                new ChildrenMapping('geometries', 'children_geometry_', 'cg', [
                    new ChildrenMapping('module', 'children_module_', 'cm', [
                        new ChildrenMapping('type', 'children_type_', 'cty'),
                    ]),
                ]),
            ], $childrenWheres),
        ])
            ->shouldBeCalledOnce()
            ->willReturn($selectQuery)
        ;

        $this->assertEquals(
            $this->loadModel($selectQuery, Blueprint::class),
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
