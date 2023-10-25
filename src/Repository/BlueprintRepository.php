<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Enum\Blueprint\Geometry as GeometryEnum;
use GibsonOS\Module\Hc\Model\Blueprint;
use GibsonOS\Module\Hc\Model\Blueprint\Geometry;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;

class BlueprintRepository extends AbstractRepository
{
    public function __construct(
        #[GetTableName(Geometry::class)]
        private readonly string $geometryTableName,
        #[GetTableName(Module::class)]
        private readonly string $moduleTableName,
        #[GetTableName(Type::class)]
        private readonly string $typeTableName,
    ) {
    }

    public function getExpanded(int $blueprintId, array $childrenTypes): Blueprint
    {
        $blueprint = $this->fetchOne('`id`=?', [$blueprintId], Blueprint::class);
        $children = $this->collectChildren($blueprint, $childrenTypes);
        $children[$blueprint->getId() ?? 0] = $blueprint;

        $geometryTable = $this->getTable($this->geometryTableName);
        $geometryTable
            ->setWhere('`blueprint_id` IN (' . $geometryTable->getParametersString(array_keys($children)) . ')')
            ->appendJoinLeft(
                sprintf('`%s` `m`', $this->moduleTableName),
                sprintf('`m`.`id`=`%s`.`module_id`', $this->geometryTableName),
            )
            ->appendJoinLeft(sprintf('`%s` `t`', $this->typeTableName), '`t`.`id`=`m`.`type_id`')
            ->setWhereParameters(array_keys($children))
            ->setSelectString(sprintf('`%s`.*, `m`.*, `t`.*', $this->geometryTableName))
        ;

        //        $geometryTable->fields
        if ($geometryTable->selectPrepared() === false) {
            $exception = new SelectError($geometryTable->connection->error());
            $exception->setTable($geometryTable);

            throw $exception;
        }

        if ($geometryTable->countRecords() === 0) {
            return $blueprint;
        }

        $geometries = [];
        $modules = [];
        error_log($geometryTable->sql);
        foreach ($geometryTable->getRecords() as $record) {
            error_log(var_export($record, true));
            $geometryBlueprintId = (int) ($record['blueprintId'] ?? 0);

            $geometry = (new Geometry())
                ->setId((int) ($record['geometryId'] ?? 0))
                ->setBlueprint($children[$geometryBlueprintId])
                ->setType(constant(sprintf('%s::%s', GeometryEnum::class, $record['type'])))
                ->setTop((int) ($record['top'] ?? 0))
                ->setLeft((int) ($record['left'] ?? 0))
                ->setWidth((int) ($record['width'] ?? 0))
                ->setHeight((int) ($record['height'] ?? 0))
            ;
            $geometries[$geometryBlueprintId] ??= [];
            $geometries[$geometryBlueprintId][] = $geometry;
            $moduleId = $record['moduleId'];

            if ($moduleId === null) {
                continue;
            }

            $moduleId = (int) $moduleId;
            $modules[$moduleId] ??= (new Module())
                ->setId($moduleId)
                ->setModuleId((int) ($record['moduleModuleId'] ?? 0))
                ->setOptions(JsonUtility::decode($record['options'] ?? '[]'))
            ;
            $geometry->setModule($modules[$moduleId]);
        }

        foreach ($geometries as $geometryBlueprintId => $childGeometries) {
            $children[$geometryBlueprintId]->setGeometries($childGeometries);
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
