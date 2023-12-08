<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Enum\Direction;
use GibsonOS\Module\Hc\Model\Log;
use JsonException;
use MDO\Enum\OrderDirection;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class LogRepository extends AbstractRepository
{
    public function create(int $type, string $data, Direction $direction): Log
    {
        return (new Log($this->getRepositoryWrapper()->getModelWrapper()))
            ->setType($type)
            ->setRawData($data)
            ->setDirection($direction)
        ;
    }

    /**
     * @throws SelectError
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     */
    public function getById(int $id): Log
    {
        return $this->fetchOne('`id`=?', [$id], Log::class);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     */
    public function getLastEntryByModuleId(
        int $moduleId,
        int $command = null,
        int $type = null,
        Direction $direction = null
    ): Log {
        $completeWhere = $this->completeWhere($command, $type, $direction);
        $completeWhere['parameters']['moduleId'] = $moduleId;

        return $this->fetchOne(
            '`module_id`=:moduleId' . $completeWhere['where'],
            $completeWhere['parameters'],
            Log::class,
            orderBy: ['`id`' => OrderDirection::DESC],
        );
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     */
    public function getLastEntryByMasterId(
        int $masterId,
        int $command = null,
        int $type = null,
        Direction $direction = null
    ): Log {
        $completeWhere = $this->completeWhere($command, $type, $direction);
        $completeWhere['parameters']['masterId'] = $masterId;

        return $this->fetchOne(
            '`master_id`=:masterId' . $completeWhere['where'],
            $completeWhere['parameters'],
            Log::class,
            orderBy: ['`id`' => OrderDirection::DESC],
        );
    }

    /**
     * @return array{where: string, parameters: array}
     */
    private function completeWhere(
        int $command = null,
        int $type = null,
        Direction $direction = null
    ): array {
        $where = '';
        $parameters = [];

        if ($command !== null) {
            $where .= ' AND `command`=:command';
            $parameters['command'] = $command;
        }

        if ($type !== null) {
            $where .= ' AND `type`=:type';
            $parameters['type'] = $type;
        }

        if ($direction !== null) {
            $where .= ' AND `direction`=:direction';
            $parameters['direction'] = $direction->value;
        }

        return ['where' => $where, 'parameters' => $parameters];
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     */
    public function getPreviousEntryByModuleId(
        int $id,
        int $moduleId,
        int $command = null,
        int $type = null,
        Direction $direction = null
    ): Log {
        $completeWhere = $this->completeWhere($command, $type, $direction);
        $completeWhere['parameters']['id'] = $id;
        $completeWhere['parameters']['moduleId'] = $moduleId;

        return $this->fetchOne(
            '`id`<:id AND `module_id`=:moduleId' . $completeWhere['where'],
            $completeWhere['parameters'],
            Log::class,
            orderBy: ['`id`' => OrderDirection::DESC],
        );
    }
}
