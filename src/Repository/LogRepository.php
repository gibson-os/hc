<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Log;

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
        $table->setWhere('`module_id`=' . $moduleId . $this->completeWhere($command, $type, $direction));
        $table->setLimit(1);
        $table->setOrderBy('`id` DESC');

        if (!$table->select()) {
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
        $table->setWhere('`master_id`=' . $masterId . $this->completeWhere($command, $type, $direction));
        $table->setLimit(1);
        $table->setOrderBy('`id` DESC');

        if (!$table->select()) {
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
    private function completeWhere(int $command = null, int $type = null, string $direction = null)
    {
        $where = '';

        if ($command !== null) {
            $where .= ' AND `command`=' . $command;
        }

        if ($type !== null) {
            $where .= ' AND `type`=' . $type;
        }

        if ($direction !== null) {
            $where .= ' AND `direction`=' . $this->escape($direction);
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
        $table->setWhere(
            '`id`<' . $this->escape((string) $id) . ' AND ' .
            '`module_id`=' . $this->escape((string) $moduleId) .
            $this->completeWhere($command, $type, $direction)
        );
        $table->setLimit(1);
        $table->setOrderBy('`id` DESC');

        if (!$table->select()) {
            $exception = new SelectError('Kein Log Eintrag für das Modul gefunden!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new Log();
        $model->loadFromMysqlTable($table);

        return $model;
    }
}
