<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Blueprint;
use GibsonOS\Module\Hc\Model\Blueprint\Geometry;

class BlueprintRepository extends AbstractRepository
{
    public function __construct(#[GetTableName(Geometry::class)] private readonly string $geometryTableName)
    {
    }

    public function getExpanded(int $blueprintId, array $childrenTypes): Blueprint
    {
        $blueprint = $this->fetchOne('`id`=?', [$blueprintId], Blueprint::class);
        $children = $this->collectChildren($blueprint, $childrenTypes);
        $children[$blueprint->getId() ?? 0] = $blueprint;

        $geometryTable = $this->getTable($this->geometryTableName);
        $geometryTable
            ->setWhere('`blueprint_id` IN (' . $geometryTable->getParametersString(array_keys($children)) . ')')
            ->setWhereParameters(array_keys($children))
        ;

        $geometries = [];

        foreach ($this->getModels($geometryTable, Geometry::class) as $geometry) {
            $geometryBlueprintId = $geometry->getBlueprintId();
            $geometry->setBlueprint($children[$geometryBlueprintId]);
            $geometries[$geometryBlueprintId] ??= [];
            $geometries[$geometryBlueprintId][] = $geometry;
        }

        foreach ($geometries as $geometryBlueprintId => $childGeometries) {
            $childrenBlueprint = $children[$geometryBlueprintId];
            $childrenBlueprint->setGeometries($childGeometries);
        }

        return $blueprint;
    }

    /**
     * @throws SelectError
     */
    public function getById(int $id): Blueprint
    {
        return $this->fetchOne('`id`=?', [$id], Blueprint::class);
    }

    /**
     * @throws SelectError
     *
     * @return Blueprint[]
     */
    public function findByName(string $name): array
    {
        return $this->fetchAll('`name` LIKE ?', [$name . '%'], Blueprint::class);
    }

    /**
     * @throws SelectError
     *
     * @return array<int, Blueprint>
     */
    private function collectChildren(Blueprint $blueprint, array $childrenTypes): array
    {
        if (count($childrenTypes) === 0) {
            $blueprint->setChildren([]);

            return [];
        }

        $children = [];
        $parameters = $childrenTypes;
        $parameters[] = $blueprint->getId();
        $childrenModels = $this->fetchAll(
            '`type` IN (' . $this->getTable($this->geometryTableName)->getParametersString($childrenTypes) . ') AND `parent_id`=?',
            $parameters,
            Blueprint::class,
        );
        $blueprint->setChildren($childrenModels);

        foreach ($childrenModels as $child) {
            $children = $this->collectChildren($child, $childrenTypes);
            $children[$child->getId() ?? 0] = $child;
        }

        return $children;
    }
}
