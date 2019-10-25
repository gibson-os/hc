<?php
namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Log as LogModel;

class Log extends AbstractRepository
{
    /**
     * @param int $moduleId
     * @param null|int $command
     * @param null|int $type
     * @param null|string $direction
     * @return LogModel
     * @throws SelectError
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
     * @param int $masterId
     * @param null|int $command
     * @param null|int $type
     * @param null|string $direction
     * @return LogModel
     * @throws SelectError
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
     * @param null|int $command
     * @param null|int $type
     * @param null|string $direction
     * @return string
     */
    private static function completeWhere($command = null, $type = null, $direction = null)
    {
        $where = '';

        if (!is_null($command)) {
            $where .= ' AND `command`=' . self::escape($command);
        }

        if (!is_null($type)) {
            $where .= ' AND `type`=' . self::escape($type);
        }

        if (!is_null($direction)) {
            $where .= ' AND `direction`=' . self::escape($direction);
        }

        return $where;
    }
    /**
     * @param int $id
     * @param int $moduleId
     * @param null|int $command
     * @param null|int $type
     * @param null|string $direction
     * @return LogModel
     * @throws SelectError
     */
    public static function getPreviewEntryByModuleId($id, $moduleId, $command = null, $type = null, $direction = null)
    {
        $table = self::getTable(LogModel::getTableName());
        $table->setWhere(
            '`id`<' . self::escape($id) . ' AND ' .
            '`module_id`=' . self::escape($moduleId) .
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