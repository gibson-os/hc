<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use DateTime;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Factory\FormatterFactory;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use mysqlDatabase;

class LogStore extends AbstractDatabaseStore
{
    private FormatterFactory $formatterFactory;

    public function __construct(FormatterFactory $formatterFactory, mysqlDatabase $database = null)
    {
        parent::__construct($database);
        $this->formatterFactory = $formatterFactory;
    }

    protected function getTableName(): string
    {
        return Log::getTableName();
    }

    public function setMasterId(?int $masterId): LogStore
    {
        if ($masterId === null) {
            unset($this->where['masterId']);
        } else {
            $this->where['masterId'] = '`' . $this->getTableName() . '`.`master_id`=' . $masterId;
        }

        return $this;
    }

    public function setModuleId(?int $moduleId): LogStore
    {
        if ($moduleId === null) {
            unset($this->where['moduleId']);
        } else {
            $this->where['moduleId'] = '`' . $this->getTableName() . '`.`module_id`=' . $moduleId;
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

    public function setTypes(?array $types): LogStore
    {
        if (empty($types)) {
            unset($this->where['types']);

            return $this;
        }

        $this->where['types'] = '`' . $this->getTableName() . '`.`type` IN (' . $this->database->implode($types) . ')';

        return $this;
    }

    /**
     * @throws DateTimeError
     *
     * @return Log[]
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
        //$this->table->setOrderBy('`' . $this->getTableName() . '`.`id` DESC');
        $this->table->setOrderBy($this->getOrderBy());
        $this->table->select(
            false,
            '`' . $this->getTableName() . '`.`id`, ' .
            '`' . $this->getTableName() . '`.`data`, ' .
            '`' . $this->getTableName() . '`.`raw_data`, ' .
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
                ->setType((int) $log['type'])
                ->setData($log['data'])
                ->setRawData($log['raw_data'])
                ->setId((int) $log['id'])
                ->setAdded(new DateTime($log['added']))
                ->setCommand($log['command'] === null ? null : (int) $log['command'])
                ->setDirection($log['direction'])
                ->setSlaveAddress((int) $log['address'])
            ;

            $module = null;

            if ($log['module_id']) {
                $module = (new Module())
                    ->setId((int) $log['module_id'])
                    ->setName($log['name'])
                    ->setDeviceId((int) $log['device_id'])
                    ->setConfig($log['config'])
                    ->setHertz((int) $log['hertz'])
                    ->setAddress((int) $log['address'])
                    ->setIp((int) $log['ip'])
                    ->setOffline((bool) $log['offline'])
                    ->setAdded(empty($log['module_added']) ? null : new DateTime($log['module_added']))
                    ->setModified(new DateTime(empty($log['module_modified']) ? 'now' : $log['module_modified']))
                    ->setType(
                        (new Type())
                            ->setId((int) $log['type_id'])
                            ->setName($log['type_name'])
                            ->setHertz((int) $log['type_hertz'])
                            ->setNetwork((int) $log['network'])
                            ->setUiSettings($log['ui_settings'])
                            ->setHelper($log['helper'])
                    )
                ;
                $logModel->setModule($module);
            }

            if ($log['master_id']) {
                $master = (new Master())
                    ->setId((int) $log['master_id'])
                    ->setName($log['master_name'])
                    ->setProtocol($log['master_protocol'])
                    ->setAddress($log['master_address'])
                ;
                $logModel->setMaster($master);

                if ($module instanceof Module) {
                    $module->setMaster($master);
                }
            }

            $formatter = $this->formatterFactory->get($logModel);
            $logModel
                ->setText($formatter->text($logModel))
                ->setRendered($formatter->render($logModel))
                ->setCommandText($formatter->command($logModel))
            ;
            $data[] = $logModel;
        }

        return $data;
    }

    public function getCountField(): string
    {
        return '`' . $this->getTableName() . '`.`id`';
    }

    public function getTraffic(): int
    {
        $this->table->clearJoin();
        $this->table->setOrderBy();
        $this->table->setWhere($this->getWhere());

        $traffic = $this->table->selectAggregate(
            'LENGTH(GROUP_CONCAT(`' . $this->getTableName() . '`.`raw_data` SEPARATOR \'\'))+' .
            '(COUNT(`hc_log`.`id`)*3)'
        );

        if (empty($traffic)) {
            return 0;
        }

        return (int) $traffic[0];
    }

    /**
     * @return string[]
     */
    protected function getOrderMapping(): array
    {
        return [
            'added' => '`' . $this->getTableName() . '`.`added`',
            //'masterName' => '`hc_master`.`name`',
            //'moduleName' => '`hc_module`.`name`',
            'direction' => '`' . $this->getTableName() . '`.`direction`',
            'type' => '`' . $this->getTableName() . '`.`type`',
            'command' => '`' . $this->getTableName() . '`.`command`',
        ];
    }
}
