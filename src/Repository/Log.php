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
     * @param int         $moduleId
     * @param int|null    $command
     * @param int|null    $type
     * @param string|null $direction
     *
     * @throws SelectError
     *
     * @return LogModel
     */
    public static function getLastEntryByModuleId($moduleId, $command = null, $type = null, $direction = null)
    {
        $table = self::getTable(LogModel::getTableName());
        $table->setWhere('`module_id`=' . self::escape($moduleId) . self::completeWhere($command, $type, $direction));
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
     * @param int         $masterId
     * @param int|null    $command
     * @param int|null    $type
     * @param string|null $direction
     *
     * @throws SelectError
     *
     * @return LogModel
     */
    public static function getLastEntryByMasterId($masterId, $command = null, $type = null, $direction = null)
    {
        $table = self::getTable(LogModel::getTableName());
        $table->setWhere('`master_id`=' . self::escape($masterId) . self::completeWhere($command, $type, $direction));
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
     * @param int|null    $command
     * @param int|null    $type
     * @param string|null $direction
     *
     * @return string
     */
    private static function completeWhere($command = null, $type = null, $direction = null)
    {
        $where = '';

        if (null !== $command) {
            $where .= ' AND `command`=' . self::escape($command);
        }

        if (null !== $type) {
            $where .= ' AND `type`=' . self::escape($type);
        }

        if (null !== $direction) {
            $where .= ' AND `direction`=' . self::escape($direction);
        }

        return $where;
    }

    /**
     * @param int         $id
     * @param int         $moduleId
     * @param int|null    $command
     * @param int|null    $type
     * @param string|null $direction
     *
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
