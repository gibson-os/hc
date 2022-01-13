<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use DateTimeImmutable;
use Exception;
use GibsonOS\Core\Service\DateTimeService;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Factory\FormatterFactory;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use mysqlDatabase;

class LogStore extends AbstractDatabaseStore
{
    private ?int $masterId = null;

    private ?int $moduleId = null;

    private ?string $direction = null;

    private array $types = [];

    public function __construct(
        private FormatterFactory $formatterFactory,
        private DateTimeService $dateTimeService,
        mysqlDatabase $database = null
    ) {
        parent::__construct($database);
    }

    protected function getModelClassName(): string
    {
        return Log::class;
    }

    protected function setWheres(): void
    {
        if ($this->masterId !== null) {
            $this->addWhere('`master_id`=?', [$this->masterId]);
        }

        if ($this->moduleId !== null) {
            $this->addWhere('`module_id`=?', [$this->moduleId]);
        }

        if ($this->direction !== null) {
            $this->addWhere('`direction`=?', [$this->direction]);
        }

        if (count($this->types) > 0) {
            $this->addWhere('`type` IN (' . $this->table->getParametersString($this->types) . ')', $this->types);
        }
    }

    public function getMasterId(): ?int
    {
        return $this->masterId;
    }

    public function setMasterId(?int $masterId): LogStore
    {
        $this->masterId = $masterId;

        return $this;
    }

    public function getModuleId(): ?int
    {
        return $this->moduleId;
    }

    public function setModuleId(?int $moduleId): LogStore
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function setDirection(?string $direction): LogStore
    {
        $this->direction = $direction;

        return $this;
    }

    public function setTypes(array $types): LogStore
    {
        $this->types = $types;

        return $this;
    }

    protected function initTable(): void
    {
        parent::initTable();

        $tableName = $this->getTableName();
        $moduleTableName = Module::getTableName();
        $this->table
            ->appendJoinLeft(
                Master::getTableName(),
                '`' . Master::getTableName() . '`.`id`=`' . $tableName . '`.`master_id`'
            )
            ->appendJoinLeft(
                $moduleTableName,
                '`' . $moduleTableName . '`.`id`=`' . $tableName . '`.`module_id`'
            )
            ->appendJoinLeft(
                Type::getTableName(),
                '`' . $moduleTableName . '`.`type_id`=`' . Type::getTableName() . '`.`id`'
            )
        ;
    }

    /**
     * @throws Exception
     *
     * @return Log[]
     */
    public function getList(): array
    {
        $this->initTable();
        $this->table->selectPrepared(
            false,
            '`hc_log`.`id`, ' .
            '`hc_log`.`data`, ' .
            '`hc_log`.`raw_data`, ' .
            '`hc_log`.`direction`, ' .
            '`hc_log`.`type`, ' .
            '`hc_log`.`command`, ' .
            '`hc_log`.`added`, ' .
            '`hc_log`.`module_id`, ' .
            '`hc_log`.`master_id`, ' .
            '`hc_master`.`id` AS `master_id`, ' .
            '`hc_master`.`name` AS `master_name`, ' .
            '`hc_master`.`protocol` AS `master_protocol`, ' .
            '`hc_master`.`address` AS `master_address`, ' .
            '`hc_module`.`name`, ' .
            '`hc_module`.`device_id`, ' .
            '`hc_module`.`config`, ' .
            '`hc_module`.`hertz`, ' .
            '`hc_module`.`pwm_speed`, ' .
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
                ->setData((string) $log['data'])
                ->setRawData((string) $log['raw_data'])
                ->setId((int) $log['id'])
                ->setAdded($this->dateTimeService->get((string) $log['added']))
                ->setCommand($log['command'] === null ? null : (int) $log['command'])
                ->setDirection((string) $log['direction'])
                ->setSlaveAddress((int) $log['address'])
            ;

            $module = null;

            if ($log['module_id']) {
                $module = (new Module())
                    ->setId((int) $log['module_id'])
                    ->setName((string) $log['name'])
                    ->setDeviceId((int) $log['device_id'])
                    ->setConfig($log['config'] === null ? null : (string) $log['config'])
                    ->setHertz((int) $log['hertz'])
                    ->setPwmSpeed((int) $log['pwm_speed'])
                    ->setAddress((int) $log['address'])
                    ->setIp((int) $log['ip'])
                    ->setOffline((bool) $log['offline'])
                    ->setAdded(
                        empty($log['module_added'])
                            ? new DateTimeImmutable()
                            : $this->dateTimeService->get((string) $log['module_added'])
                    )
                    ->setModified(
                        $this->dateTimeService->get(empty($log['module_modified'])
                            ? 'now'
                            : (string) $log['module_modified'])
                    )
                    ->setType(
                        (new Type())
                            ->setId((int) $log['type_id'])
                            ->setName((string) $log['type_name'])
                            ->setHertz((int) $log['type_hertz'])
                            ->isNetwork((bool) $log['network'])
                            ->setUiSettings($log['ui_settings'] === null ? null : (string) $log['ui_settings'])
                            ->setHelper((string) $log['helper'])
                    )
                ;
                $logModel->setModule($module);
            }

            if ($log['master_id']) {
                $master = (new Master())
                    ->setId((int) $log['master_id'])
                    ->setName((string) $log['master_name'])
                    ->setProtocol((string) $log['master_protocol'])
                    ->setAddress((string) $log['master_address'])
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
                ->setExplains($formatter->explain($logModel))
            ;
            $data[] = $logModel;
        }

        return $data;
    }

    public function getCountField(): string
    {
        return '`hc_log`.`id`';
    }

    public function getTraffic(): int
    {
        $this->initTable();
        $this->table
            ->clearJoin()
            ->setOrderBy()
            ->setWhere($this->getWhereString())
            ->setWhereParameters($this->getWhereParameters())
        ;

        $traffic = $this->table->selectAggregatePrepared(
            'LENGTH(GROUP_CONCAT(`hc_log`.`raw_data` SEPARATOR \'\'))+' .
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
            'added' => '`hc_log`.`added`',
            //'masterName' => '`hc_master`.`name`',
            //'moduleName' => '`hc_module`.`name`',
            'direction' => '`hc_log`.`direction`',
            'type' => '`hc_log`.`type`',
            'command' => '`hc_log`.`command`',
        ];
    }
}
