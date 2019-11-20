<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use DateTime;
use Exception;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Factory\Formatter;
use GibsonOS\Module\Hc\Service\ServerService;

class LogStore extends AbstractDatabaseStore
{
    /**
     * @return string
     */
    protected function getTableName(): string
    {
        return 'hc_log';
    }

    /**
     * @param int $masterId
     *
     * @return LogStore
     */
    public function setMasterId($masterId): LogStore
    {
        if ($masterId === 0) {
            unset($this->where['masterId']);
        } else {
            $this->where['masterId'] = '`' . $this->getTableName() . '`.`master_id`=' . $this->database->escape($masterId);
        }

        return $this;
    }

    /**
     * @param int $moduleId
     *
     * @return LogStore
     */
    public function setModuleId(int $moduleId): LogStore
    {
        if ($moduleId === 0) {
            unset($this->where['moduleId']);
        } else {
            $this->where['moduleId'] = '`' . $this->getTableName() . '`.`module_id`=' . $this->database->escape($moduleId);
        }

        return $this;
    }

    /**
     * @param string|null $direction
     *
     * @return LogStore
     */
    public function setDirection(?string $direction): LogStore
    {
        if ($direction === null) {
            unset($this->where['direction']);
        } else {
            $this->where['direction'] = '`' . $this->getTableName() . '`.`direction`=' . $this->database->escape($direction);
        }

        return $this;
    }

    /**
     * @param array $types
     *
     * @return LogStore
     */
    public function setTypes(array $types): LogStore
    {
        if (!empty($types)) {
            unset($this->where['types']);

            return $this;
        }

        $this->where['types'] = '`' . $this->getTableName() . '`.`type` IN (' . $this->database->implode($types) . ')';

        return $this;
    }

    /**
     * @throws FileNotFound
     * @throws Exception
     *
     * @return array[]
     */
    public function getList(): array
    {
        $this->table->appendJoinLeft(
            '`gibson_os`.`hc_master`',
            '`hc_master`.`id`=`hc_log`.`master_id`'
        );
        $this->table->appendJoinLeft(
            '`gibson_os`.`hc_module`',
            '`hc_module`.`id`=`hc_log`.`module_id`'
        );
        $this->table->appendJoinLeft(
            '`gibson_os`.`hc_type`',
            '`hc_module`.`type_id`=`hc_type`.`id`'
        );

        $this->table->setWhere($this->getWhere());
        $this->table->setOrderBy('`' . $this->getTableName() . '`.`id` DESC');
        //$this->table->setOrderBy($this->getOrderBy());
        $this->table->select(
            false,
            '`' . $this->getTableName() . '`.`id`, ' .
            '`' . $this->getTableName() . '`.`data`, ' .
            '`' . $this->getTableName() . '`.`direction`, ' .
            '`' . $this->getTableName() . '`.`type`, ' .
            '`' . $this->getTableName() . '`.`command`, ' .
            '`' . $this->getTableName() . '`.`added`, ' .
            '`' . $this->getTableName() . '`.`module_id`, ' .
            '`' . $this->getTableName() . '`.`master_id`, ' .
            '`hc_master`.`id` AS `master_id`, ' .
            '`hc_master`.`name` AS `master_name`, ' .
            '`hc_master`.`protocol` AS `master_protocol`, ' .
            '`hc_master`.`address` AS `master_address`, ' .
            '`hc_module`.`name`, ' .
            '`hc_module`.`device_id`, ' .
            '`hc_module`.`config`, ' .
            '`hc_module`.`hertz`, ' .
            '`hc_module`.`address`, ' .
            '`hc_module`.`ip`, ' .
            '`hc_module`.`offline`, ' .
            '`hc_module`.`added` AS `module_added`, ' .
            '`hc_module`.`modified` AS `module_modified`, ' .
            '`hc_type`.`id` AS `type_id`, ' .
            '`hc_type`.`name` AS `type_name`, ' .
            '`hc_type`.`hertz` AS `type_hertz`, ' .
            '`hc_type`.`network`, ' .
            '`hc_type`.`ui_settings`, ' .
            '`hc_type`.`helper`'
        );

        $data = [];

        foreach ($this->table->connection->fetchAssocList() as $log) {
            $formatter = Formatter::createByLog($log);

            $data[] = [
                'id' => $log['id'],
                'master' => $log['master_name'],
                'module' => $log['name'],
                'type' => $log['type'],
                'command' => $formatter->command(),
                'helper' => $log['helper'],
                'text' => $formatter->text(),
                'rendered' => $formatter->render(),
                'plain' => $log['data'],
                'added' => (new DateTime($log['added']))->format('Y-m-d H:i:s'),
                'direction' => $log['direction'] === ServerService::DIRECTION_INPUT ? 1 : 0,
            ];
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getCountField(): string
    {
        return '`' . $this->getTableName() . '`.`id`';
    }

    /**
     * @return mixed
     */
    public function getTraffic()
    {
        $this->table->clearJoin();
        $this->table->setOrderBy(null);
        $this->table->setWhere($this->getWhere());

        $traffic = $this->table->selectAggregate(
            'ROUND((LENGTH(GROUP_CONCAT(`' . $this->getTableName() . '`.`data` SEPARATOR \'\'))/2)+' .
            '(COUNT(`hc_log`.`id`)*3), 0)'
        );

        return $traffic[0];
    }

    /**
     * @return string[]
     */
    protected function getOrderMapping(): array
    {
        return [
            'added' => '`' . $this->getTableName() . '`.`added`',
            'master' => '`hc_master`.`name`',
            'module' => '`hc_module`.`name`',
            'direction' => '`' . $this->getTableName() . '`.`direction`',
            'type' => '`' . $this->getTableName() . '`.`type`',
            'command' => '`' . $this->getTableName() . '`.`command`',
        ];
    }
}
