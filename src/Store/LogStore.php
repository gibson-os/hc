<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use DateTime;
use Exception;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Factory\FormatterFactory;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;

class LogStore extends AbstractDatabaseStore
{
    protected function getTableName(): string
    {
        return 'hc_log';
    }

    /**
     * @param int $masterId
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

    public function setModuleId(int $moduleId): LogStore
    {
        if ($moduleId === 0) {
            unset($this->where['moduleId']);
        } else {
            $this->where['moduleId'] = '`' . $this->getTableName() . '`.`module_id`=' . $this->database->escape($moduleId);
        }

        return $this;
    }

    public function setDirection(?string $direction): LogStore
    {
        if ($direction === null) {
            unset($this->where['direction']);
        } else {
            $this->where['direction'] = '`' . $this->getTableName() . '`.`direction`=' . $this->database->escape($direction);
        }

        return $this;
    }

    public function setTypes(array $types): LogStore
    {
        if (empty($types)) {
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
            $logModel = (new Log())
                ->setType($log['type'])
                ->setModuleId($log['module_id'])
                ->setModule(
                    (new Module())
                    ->setId($log['module_id'])
                    ->setName($log['name'])
                    ->setDeviceId($log['device_id'])
                    ->setConfig($log['config'])
                    ->setHertz($log['hertz'])
                    ->setAddress($log['address'])
                    ->setIp($log['ip'])
                    ->setOffline($log['offline'])
                    ->setAdded($log['module_added'])
                    ->setModified($log['module_modifies'])
                    ->setTypeId($log['type_id'])
                    ->setType(
                        (new Type())
                        ->setId($log['type_id'])
                        ->setName($log['type_name'])
                        ->setHertz($log['type_hertz'])
                        ->setNetwork($log['network'])
                        ->setUiSettings($log['ui_settings'])
                        ->setHelper($log['helper'])
                    )
                    ->setMasterId($log['master_id'])
                    ->setMaster(
                        (new Master())
                        ->setName($log['name'])
                        ->setProtocol($log['protocol'])
                        ->setAddress($log['address'])
                    )
                )
                ->setData($log['data'])
                ->setId($log['id'])
                ->setAdded($log['added'])
                ->setCommand($log['command'])
                ->setDirection($log['direction'])
                ->setMasterId($log['master_id'])
                ->setSlaveAddress($log['address'])
            ;
            $formatter = FormatterFactory::create($logModel);

            $data[] = [
                'id' => $log['id'],
                'master' => $log['master_name'],
                'module' => $log['name'],
                'type' => $log['type'],
                'command' => $formatter->command($logModel),
                'helper' => $log['helper'],
                'text' => $formatter->text($logModel),
                'rendered' => $formatter->render($logModel),
                'plain' => $log['data'],
                'added' => (new DateTime($log['added']))->format('Y-m-d H:i:s'),
                'direction' => $log['direction'] === Log::DIRECTION_INPUT ? 1 : 0,
            ];
        }

        return $data;
    }

    public function getCountField(): string
    {
        return '`' . $this->getTableName() . '`.`id`';
    }

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
