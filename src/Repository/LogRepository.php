<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Dto\Direction;
use GibsonOS\Module\Hc\Model\Log;
use mysqlTable;

class LogRepository extends AbstractRepository
{
    public function __construct(#[GetTableName(Log::class)] private string $logTableName)
    {
    }

    public function create(int $type, string $data, Direction $direction): Log
    {
        return (new Log())
            ->setType($type)
            ->setRawData($data)
            ->setDirection($direction)
        ;
    }

    /**
     * @throws SelectError
     */
    public function getById(int $id): Log
    {
        return $this->fetchOne('`id`=?', [$id], Log::class);
    }

    /**
     * @throws SelectError
     */
    public function getLastEntryByModuleId(
        int $moduleId,
        int $command = null,
        int $type = null,
        string $direction = null
    ): Log {
        $table = $this->getTable($this->logTableName);
        $table
            ->addWhereParameter($moduleId)
            ->setWhere('`module_id`=?' . $this->completeWhere($table, $command, $type, $direction))
            ->setLimit(1)
            ->setOrderBy('`id` DESC')
        ;

        if (!$table->selectPrepared()) {
            $exception = new SelectError('Kein Log Eintrag für das Modul gefunden!');
            $exception->setTable($table);

            throw $exception;
        }

        return $this->getModel($table, Log::class);
    }

    /**
     * @throws SelectError
     */
    public function getLastEntryByMasterId(
        int $masterId,
        int $command = null,
        int $type = null,
        string $direction = null
    ): Log {
        $table = $this->getTable($this->logTableName);
        $table
            ->addWhereParameter($masterId)
            ->setWhere('`master_id`=?' . $this->completeWhere($table, $command, $type, $direction))
            ->setLimit(1)
            ->setOrderBy('`id` DESC')
        ;

        if (!$table->selectPrepared()) {
            $exception = new SelectError('Kein Log Eintrag für den Master gefunden!');
            $exception->setTable($table);

            throw $exception;
        }

        return $this->getModel($table, Log::class);
    }

    private function completeWhere(
        mysqlTable $table,
        int $command = null,
        int $type = null,
        string $direction = null
    ): string {
        $where = '';

        if ($command !== null) {
            $where .= ' AND `command`=?';
            $table->addWhereParameter($command);
        }

        if ($type !== null) {
            $where .= ' AND `type`=?';
            $table->addWhereParameter($type);
        }

        if ($direction !== null) {
            $where .= ' AND `direction`=?';
            $table->addWhereParameter($direction);
        }

        return $where;
    }

    /**
     * @throws SelectError
     */
    public function getPreviousEntryByModuleId(
        int $id,
        int $moduleId,
        int $command = null,
        int $type = null,
        string $direction = null
    ): Log {
        $table = $this->getTable($this->logTableName);
        $table
            ->setWhereParameters([$id, $moduleId])
            ->setWhere('`id`<? AND `module_id`=?' . $this->completeWhere($table, $command, $type, $direction))
            ->setLimit(1)
            ->setOrderBy('`id` DESC')
        ;

        if (!$table->selectPrepared()) {
            $exception = new SelectError('Kein Log Eintrag für das Modul gefunden!');
            $exception->setTable($table);

            throw $exception;
        }

        return $this->getModel($table, Log::class);
    }
}
