<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Dto\Model\ChildrenMapping;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Blueprint;
use JsonException;
use MDO\Dto\Query\Where;
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
        return $this->fetchOne(
            '`id`=?',
            [$blueprintId],
            Blueprint::class,
            children: [
                new ChildrenMapping('geometries', 'geometry_', 'g', [
                    new ChildrenMapping('module', 'module_', 'm', [
                        new ChildrenMapping('type', 'type_', 't'),
                    ]),
                ]),
                new ChildrenMapping('children', 'children_', 'c', [
                    new ChildrenMapping('geometries', 'children_geometry_', 'cg', [
                        new ChildrenMapping('module', 'children_module_', 'cm', [
                            new ChildrenMapping('type', 'children_type_', 'ct'),
                        ]),
                    ]),
                ], [
                    new Where(
                        sprintf(
                            '`c`.`type` IN (%s)',
                            $this->getRepositoryWrapper()->getSelectService()->getParametersString($childrenTypes),
                        ),
                        $childrenTypes,
                    ),
                ]),
            ],
        );
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
}
