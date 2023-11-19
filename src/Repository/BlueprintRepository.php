<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Dto\Model\ChildrenMapping;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Blueprint;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class BlueprintRepository extends AbstractRepository
{
    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     */
    public function getExpanded(int $blueprintId, array $childrenTypes): Blueprint
    {
        $blueprint = $this->fetchOne(
            '`t`.`id`=:blueprintId',
            ['blueprintId' => $blueprintId],
            Blueprint::class,
            children: [
                new ChildrenMapping('geometries', 'geometry_', 'g', [
                    new ChildrenMapping('module', 'module_', 'm', [
                        new ChildrenMapping('type', 'type_', 'ty'),
                    ]),
                ]),
            ],
        );
        $blueprint->setChildren($this->collectChildren($blueprintId, $childrenTypes));

        return $blueprint;
    }

    /**
     * @throws SelectError
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     */
    public function getById(int $id): Blueprint
    {
        return $this->fetchOne('`id`=?', [$id], Blueprint::class);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Blueprint[]
     */
    public function findByName(string $name): array
    {
        return $this->fetchAll('`name` LIKE ?', [$name . '%'], Blueprint::class);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     *
     * @return array<int, Blueprint>
     */
    private function collectChildren(int $blueprintId, array $childrenTypes): array
    {
        if (count($childrenTypes) === 0) {
            return [];
        }

        $parameters = $childrenTypes;
        $parameters[] = $blueprintId;
        $children = $this->fetchAll(
            '`t`.`type` IN (' . $this->getRepositoryWrapper()->getSelectService()->getParametersString($childrenTypes) . ') AND `t`.`parent_id`=?',
            $parameters,
            Blueprint::class,
            children: [
                new ChildrenMapping('geometries', 'geometry_', 'g', [
                    new ChildrenMapping('module', 'module_', 'm', [
                        new ChildrenMapping('type', 'type_', 'ty'),
                    ]),
                ]),
            ],
        );

        foreach ($children as $child) {
            $child->setChildren($this->collectChildren($child->getId() ?? 0, $childrenTypes));
        }

        return $children;
    }
}
