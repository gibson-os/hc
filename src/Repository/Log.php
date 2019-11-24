<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Log as LogModel;

class Log extends AbstractRepository
{
    /**
     * @throws DateTimeError
     * @throws SelectError
     *
     * @return LogModel
     */
    public static function getLastEntryByModuleId(
        int $moduleId,
        int $command = null,
        int $type = null,
        string $direction = null
    ) {
        $table = self::getTable(LogModel::getTableName());
        $table->setWhere('`module_id`=' . $moduleId . self::completeWhere($command, $type, $direction));
        $table->setLimit(1);
        $table->setOrderBy('`id` DESC');

        if (!$table->select()) {
            $exception = new SelectError('Kein Log Eintrag für das Modul gefunden!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new LogModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     *
     * @return LogModel
     */
    public static function getLastEntryByMasterId(
        int $masterId,
        int $command = null,
        int $type = null,
        string $direction = null
    ) {
        $table = self::getTable(LogModel::getTableName());
        $table->setWhere('`master_id`=' . $masterId . self::completeWhere($command, $type, $direction));
        $table->setLimit(1);
        $table->setOrderBy('`id` DESC');

        if (!$table->select()) {
            $exception = new SelectError('Kein Log Eintrag für den Master gefunden!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new LogModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @return string
     */
    private static function completeWhere(int $command = null, int $type = null, string $direction = null)
    {
        $where = '';

        if ($command !== null) {
            $where .= ' AND `command`=' . $command;
        }

        if ($type !== null) {
            $where .= ' AND `type`=' . $type;
        }

        if ($direction !== null) {
            $where .= ' AND `direction`=' . self::escape($direction);
        }

        return $where;
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     *
     * @return LogModel
     */
    public static function getPreviewEntryByModuleId(
        int $id,
        int $moduleId,
        int $command = null,
        int $type = null,
        string $direction = null
    ) {
        $table = self::getTable(LogModel::getTableName());
        $table->setWhere(
            '`id`<' . self::escape((string) $id) . ' AND ' .
            '`module_id`=' . self::escape((string) $moduleId) .
            self::completeWhere($command, $type, $direction)
        );
        $table->setLimit(1);
        $table->setOrderBy('`id` DESC');

        if (!$table->select()) {
            $exception = new SelectError('Kein Log Eintrag für das Modul gefunden!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new LogModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }
}
