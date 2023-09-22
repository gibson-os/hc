<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Blueprint;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Blueprint;
use GibsonOS\Module\Hc\Model\Blueprint\Module;

class ModuleRepository extends AbstractRepository
{
    public function __construct(#[GetTableName(Module::class)] private readonly string $moduleTableName)
    {
    }

    /**
     * @throws SelectError
     *
     * @return array<int, Module[]>
     */
    public function getAllByBlueprint(Blueprint $blueprint): array
    {
        $blueprintIds = $this->getBlueprintIds($blueprint);
        $modules = $this->fetchAll(
            '`blueprint_id` IN (' . $this->getTable($this->moduleTableName)->getParametersString($blueprintIds) . ')',
            $blueprintIds,
            Module::class,
        );
        $modulesByBlueprintId = [];

        foreach ($modules as $module) {
            $blueprintId = $module->getBlueprintId();
            $modulesByBlueprintId[$blueprintId] ??= [];
            $modulesByBlueprintId[$blueprintId][] = $module;
        }

        return $modulesByBlueprintId;
    }

    private function getBlueprintIds(Blueprint $blueprint): array
    {
        $blueprintIds = [];

        foreach ($blueprint->getChildren() as $child) {
            $blueprintIds = $this->getBlueprintIds($child);
        }

        $blueprintIds[] = $blueprint->getId() ?? 0;

        return $blueprintIds;
    }
}
