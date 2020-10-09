<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Log;
use mysqlTable;

class LogRepository extends AbstractRepository
{
    public function create(int $type, string $data, string $direction): Log
    {
        return (new Log())
            ->setType($type)
            ->setData($data)
            ->setDirection($direction)
        ;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getLastEntryByModuleId(
        int $moduleId,
        int $command = null,
        int $type = null,
        string $direction = null
    ): Log {
        $table = $this->getTable(Log::getTableName());
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

        $model = new Log();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getLastEntryByMasterId(
        int $masterId,
        int $command = null,
        int $type = null,
        string $direction = null
    ): Log {
        $table = $this->getTable(Log::getTableName());
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

        $model = new Log();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @return string
     */
    private function completeWhere(mysqlTable $table, int $command = null, int $type = null, string $direction = null)
    {
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
     * @throws DateTimeError
     * @throws GetError
     * @throws SelectError
     */
    public function getPreviewEntryByModuleId(
        int $id,
        int $moduleId,
        int $command = null,
        int $type = null,
        string $direction = null
    ): Log {
        $table = $this->getTable(Log::getTableName());
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

        $model = new Log();
        $model->loadFromMysqlTable($table);

        return $model;
    }
}
