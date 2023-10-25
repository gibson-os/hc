<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Dto\Model\ChildrenMapping;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Log;
use MDO\Dto\Query\Where;
use MDO\Dto\Record;
use MDO\Query\SelectQuery;

/**
 * @extends AbstractDatabaseStore<Log>
 */
class LogStore extends AbstractDatabaseStore
{
    private ?int $masterId = null;

    private ?int $moduleId = null;

    private ?string $direction = null;

    private array $types = [];

    protected function getModelClassName(): string
    {
        return Log::class;
    }

    protected function getAlias(): ?string
    {
        return 'l';
    }

    protected function setWheres(): void
    {
        if ($this->masterId !== null) {
            $this->addWhere('`l`.`master_id`=?', [$this->masterId]);
        }

        if ($this->moduleId !== null) {
            $this->addWhere('`l`.`module_id`=?', [$this->moduleId]);
        }

        if ($this->direction !== null) {
            $this->addWhere('`l`.`direction`=?', [$this->direction]);
        }

        if (count($this->types) > 0) {
            $this->addWhere(
                sprintf(
                    '`l`.`type` IN (%s)',
                    $this->getDatabaseStoreWrapper()->getSelectService()->getParametersString($this->types)
                ),
                $this->types,
            );
        }

        foreach ($this->getWheres() as $where) {
            $this->addWhere($where->getCondition(), $where->getParameters());
        }
    }

    /**
     * @return Where[]
     */
    protected function getWheres(): array
    {
        $wheres = [];

        if ($this->masterId !== null) {
            $wheres[] = new Where('`l`.`master_id`=?', [$this->masterId]);
        }

        if ($this->moduleId !== null) {
            $wheres[] = new Where('`l`.`module_id`=?', [$this->moduleId]);
        }

        if ($this->direction !== null) {
            $wheres[] = new Where('`l`.`direction`=?', [$this->direction]);
        }

        if (count($this->types) > 0) {
            $wheres[] = new Where(
                sprintf(
                    '`l`.`type` IN (%s)',
                    $this->getDatabaseStoreWrapper()->getSelectService()->getParametersString($this->types)
                ),
                $this->types,
            );
        }

        return $wheres;
    }

    public function setMasterId(?int $masterId): LogStore
    {
        $this->masterId = $masterId;

        return $this;
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

    protected function getExtends(): array
    {
        return [
            new ChildrenMapping('master', 'master_', 'ma'),
            new ChildrenMapping('module', 'module_', 'mo', [
                new ChildrenMapping('type', 'type_', 't'),
            ]),
        ];
    }

    public function getCountField(): string
    {
        return '`l`.`id`';
    }

    public function getTraffic(): int
    {
        $selectQuery = (new SelectQuery($this->table))
            ->setWheres($this->getWheres())
            ->setSelect(
                'LENGTH(GROUP_CONCAT(`hc_log`.`raw_data` SEPARATOR \'\'))+(COUNT(`hc_log`.`id`)*3)',
                'traffic',
            )
        ;
        $result = $this->getDatabaseStoreWrapper()->getClient()->execute($selectQuery);
        /** @var Record $current */
        $current = $result->iterateRecords()->current();

        return (int) $current->get('traffic')->getValue();
    }

    /**
     * @return string[]
     */
    protected function getOrderMapping(): array
    {
        return [
            'added' => '`l`.`id`',
            // 'masterName' => '`ma`.`name`',
            // 'moduleName' => '`mo`.`name`',
            'direction' => '`l`.`direction`',
            'type' => '`l`.`type`',
            'command' => '`l`.`command`',
        ];
    }
}
