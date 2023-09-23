<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Blueprint;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Blueprint;
use GibsonOS\Module\Hc\Model\Blueprint\Geometry;
use GibsonOS\Module\Hc\Model\Blueprint\Module;

class ModuleRepository extends AbstractRepository
{
    public function __construct(
        #[GetTableName(Module::class)]
        private readonly string $moduleTableName,
        #[GetTableName(Geometry::class)]
        private readonly string $geometryTableName,
    ) {
    }

    /**
     * @throws SelectError
     *
     * @return Module[]
     */
    public function getAllByBlueprint(Blueprint $blueprint): array
    {
        $blueprintIds = $this->getBlueprintIds($blueprint);
        $table = $this->getTable($this->moduleTableName);
        $table
            ->appendJoin(
                sprintf('`%s` `g`', $this->geometryTableName),
                sprintf('`%s`.`geometry_id`=`g`.`id`', $this->moduleTableName),
            )
            ->setWhere(sprintf('`g`.`blueprint_id` IN (%s)', $table->getParametersString($blueprintIds)))
            ->setWhereParameters($blueprintIds)
        ;

        return $this->getModels($table, Module::class);
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
