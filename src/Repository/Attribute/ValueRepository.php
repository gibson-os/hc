<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Attribute;

use DateTime;
use Exception;
use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Repository\UpdateError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Module as ModuleModel;
use mysqlTable;

class ValueRepository extends AbstractRepository
{
    public function __construct(
        #[GetTableName(Attribute::class)] private string $attributeTableName,
        #[GetTableName(Value::class)] private string $valueTableName
    ) {
    }

    /**
     * @param int[]|null $moduleIds
     *
     *@throws Exception
     *
     * @return Value[]
     */
    public function getByTypeId(
        int $typeId,
        int $subId = null,
        ?array $moduleIds = [],
        ?string $type = '',
        string $key = null,
        string $order = null
    ): array {
        $separator = '#_#^#_#';
        $table = $this->getTable($this->attributeTableName);
        $table->addWhereParameter($typeId);
        $where =
            '`hc_attribute`.`type_id`=?' .
            $this->getModuleIdsWhere($table, $moduleIds) .
            $this->getTypeWhere($table, $type) .
            $this->getSubIdWhere($table, $subId)
        ;

        if (!empty($key)) {
            $where .= ' AND `hc_attribute`.`key`=?';
            $table->addWhereParameter($key);
        }

        if (!empty($order)) {
            $where .= ' AND `hc_attribute_value`.`order`=?';
            $table->addWhereParameter($order);
        }

        $table
            ->setWhere($where)
            ->appendJoin('`hc_attribute_value`', '`hc_attribute`.`id`=`hc_attribute_value`.`attribute_id`')
            ->setGroupBy('`hc_attribute`.`id`')
            ->setOrderBy('`hc_attribute`.`id`, `hc_attribute_value`.`order`')
        ;

        $select =
            '`hc_attribute`.`id`, ' .
            '`hc_attribute`.`type_id`, ' .
            '`hc_attribute`.`module_id`, ' .
            '`hc_attribute`.`sub_id`, ' .
            '`hc_attribute`.`key`, ' .
            '`hc_attribute`.`type`, ' .
            '`hc_attribute`.`added`, ' .
            'GROUP_CONCAT(`value` SEPARATOR "' . $separator . '") AS `values`, ' .
            'GROUP_CONCAT(`order` SEPARATOR "' . $separator . '") AS `orders`';

        if (!$table->selectPrepared(false, $select)) {
            return [];
        }

        $attributes = $table->connection->fetchObjectList();
        $models = [];

        foreach ($attributes as $attribute) {
            $attributeModel = (new Attribute())
                ->setId((int) $attribute->id)
                ->setTypeId(empty($attribute->type_id) ? null : (int) $attribute->type_id)
                ->setModuleId(empty($attribute->module_id) ? null : (int) $attribute->module_id)
                ->setSubId(empty($attribute->sub_id) ? null : (int) $attribute->sub_id)
                ->setKey($attribute->key)
                ->setType($attribute->type)
                ->setAdded(new DateTime($attribute->added))
            ;

            $orders = explode($separator, $attribute->orders);

            foreach (explode($separator, $attribute->values) as $pos => $value) {
                $models[] = (new Value())
                    ->setAttribute($attributeModel)
                    ->setOrder((int) $orders[$pos])
                    ->setValue($value);
            }
        }

        return $models;
    }

    /**
     * @param int[]|null $moduleIds
     *
     * @throws DeleteError
     */
    public function deleteBySubId(
        int $subId,
        int $typeId,
        ?array $moduleIds = [],
        ?string $type = '',
        string $key = null,
        string $order = null
    ): void {
        $valueTable = $this->getTable($this->valueTableName);
        $table = $this->getTable($this->attributeTableName);
        $valueTable->setWhereParameters([$subId, $typeId]);
        $where =
            '`sub_id`=? AND `type_id`=?' .
            $this->getModuleIdsWhere($valueTable, $moduleIds) .
            $this->getTypeWhere($valueTable, $type)
        ;

        if (!empty($key)) {
            $where .= ' AND `key`=?';
            $valueTable->addWhereParameter($key);
        }

        $table->setWhere($where);
        $where = '`attribute_id` IN (' . $table->getSelect('`id`') . ')';

        if (!empty($order)) {
            $where .= ' AND `order`=?';
            $valueTable->addWhereParameter($order);
        }

        $valueTable->setWhere($where);

        if (!$valueTable->deletePrepared()) {
            $exception = new DeleteError($valueTable->connection->error());
            $exception->setTable($table);

            throw $exception;
        }
    }

    /**
     * @param string[] $keys
     *
     * @throws DeleteError
     * @throws SelectError
     */
    public function deleteByModule(
        ModuleModel $module,
        int $subId = null,
        array $keys = null,
        string $type = null
    ): void {
        $table = $this->getTable($this->valueTableName);
        $table
            ->setWhereParameters([$module->getTypeId(), $module->getId()])
            ->setWhere(
                '`attribute_id` IN (' .
                    'SELECT `id` FROM `hc_attribute` WHERE ' .
                        '`type_id`=? AND `module_id`=?' .
                        $this->getTypeWhere($table, $type) .
                        $this->getSubIdWhere($table, $subId) .
                        $this->getKeysWhere($table, $keys) .
                ')'
            )
        ;

        if (!$table->deletePrepared()) {
            $exception = new DeleteError('Werte konnten nicht gelöscht werden!');
            $exception->setTable($table);

            throw $exception;
        }
    }

    /**
     * @throws UpdateError
     */
    public function updateOrder(
        int $typeId,
        int $startOrder,
        int $updateOrder,
        ?array $moduleIds = [],
        ?string $type = '',
        int $subId = null,
        string $key = null
    ): void {
        $attributeTable = $this->getTable($this->attributeTableName);
        $attributeTable
            ->addWhereParameter($typeId)
            ->setWhere(
                '`hc_attribute`.`type_id`=?' .
                $this->getModuleIdsWhere($attributeTable, $moduleIds) .
                $this->getTypeWhere($attributeTable, $type) .
                $this->getSubIdWhere($attributeTable, $subId) .
                $this->getKeysWhere($attributeTable, $key === null ? [] : [$key])
            )
        ;

        $table = $this->getTable($this->valueTableName);
        $table
            ->setWhereParameters(array_merge([$startOrder], $attributeTable->getWhereParameters()))
            ->setWhere(
                '`order`>=? AND ' .
                '`attribute_id` IN (' . $attributeTable->getSelect('`id`') . ')'
            )
        ;

        if (!$table->update('`order`=`order`+' . $updateOrder)) {
            $exception = new UpdateError($table->connection->error());
            $exception->setTable($table);

            throw $exception;
        }
    }

    /**
     * @throws SelectError
     * @throws Exception
     */
    public function findAttributesByValue(
        string $value,
        int $typeId,
        array $keys = null,
        ?array $moduleIds = [],
        int $subId = null,
        ?string $type = ''
    ): array {
        $table = $this->getTable($this->attributeTableName);
        $table
            ->setWhereParameters([$this->getRegexString($value), $typeId])
            ->setWhere(
                '`value`.`value` REGEXP ? AND ' .
                '`hc_attribute`.`type_id`=?' .
                $this->getModuleIdsWhere($table, $moduleIds) .
                $this->getSubIdWhere($table, $subId) .
                $this->getTypeWhere($table, $type) .
                $this->getKeysWhere($table, $keys)
            )
            ->appendJoin('`hc_attribute_value` AS `value`', '`hc_attribute`.`id`=`value`.`attribute_id`')
            ->appendJoin(
                '`hc_attribute` AS `attribute`',
                'IF(`hc_attribute`.`sub_id` IS NULL, `hc_attribute`.`sub_id` IS NULL, `hc_attribute`.`sub_id`=`attribute`.`sub_id`) AND ' .
                '`hc_attribute`.`type_id`=`attribute`.`type_id` AND ' .
                'IF(`hc_attribute`.`type` IS NULL, `hc_attribute`.`type` IS NULL, `hc_attribute`.`type`=`attribute`.`type`) AND ' .
                'IF(`hc_attribute`.`module_id` IS NULL, `hc_attribute`.`module_id` IS NULL, `hc_attribute`.`module_id`=`attribute`.`module_id`)'
            )
            ->appendJoin('`hc_attribute_value` AS `values`', '`attribute`.`id`=`values`.`attribute_id`')
        ;

        if (!$table->select(false, 'DISTINCT `attribute`.*, `values`.`value`, `values`.`order`')) {
            $exception = new SelectError('Keine Attribute gefunden!');
            $exception->setTable($table);

            throw $exception;
        }

        $models = [];
        $attributeModels = [];

        foreach ($table->connection->fetchObjectList() as $attribute) {
            if (!isset($attributeModels[$attribute->id])) {
                $attributeModels[$attribute->id] = (new Attribute())
                    ->setId($attribute->id)
                    ->setTypeId($attribute->type_id)
                    ->setModuleId($attribute->module_id)
                    ->setSubId($attribute->sub_id)
                    ->setKey($attribute->key)
                    ->setType($attribute->type)
                    ->setAdded(new DateTime($attribute->added))
                ;
            }

            $models[] = (new Value())
                ->setAttribute($attributeModels[$attribute->id])
                ->setOrder($attribute->order)
                ->setValue($attribute->value)
            ;
        }

        return $models;
    }

    /**
     * @return int[]
     */
    public function countByKey(string $key, int $typeId, ?array $moduleIds = [], ?string $type = ''): array
    {
        $table = $this->getTable($this->attributeTableName);
        $table
            ->setWhereParameters([$key, $typeId])
            ->setWhere(
                '`hc_attribute`.`key`=? AND `hc_attribute`.`type_id`=?' .
                $this->getModuleIdsWhere($table, $moduleIds) .
                $this->getTypeWhere($table, $type)
            )
            ->appendJoin('`hc_attribute_value` AS `value`', '`hc_attribute`.`id`=`value`.`attribute_id`')
            ->setGroupBy('`value`.`value`')
        ;

        if (!$table->select(false, '`value`.`value`, COUNT(DISTINCT `hc_attribute`.`id`) AS `count`')) {
            return [];
        }

        $counts = [];

        foreach ($table->connection->fetchObjectList() as $attribute) {
            $counts[$attribute->value] = (int) $attribute->count;
        }

        return $counts;
    }

    /**
     * @param int[]|null $moduleIds
     */
    private function getModuleIdsWhere(mysqlTable $table, ?array $moduleIds, string $tableName = 'hc_attribute'): string
    {
        if ($moduleIds === null) {
            return ' AND `' . $tableName . '`.`module_id` IS NULL';
        }

        if (empty($moduleIds)) {
            return '';
        }

        array_walk($moduleIds, function (int $moduleId) use ($table) {
            $table->addWhereParameter($moduleId);
        });

        return ' AND `' . $tableName . '`.`module_id` IN (' . $table->getParametersString($moduleIds) . ')';
    }

    private function getTypeWhere(mysqlTable $table, ?string $type, string $tableName = 'hc_attribute'): string
    {
        if ($type === null) {
            return ' AND `' . $tableName . '`.`type` IS NULL';
        }

        if (empty($type)) {
            return '';
        }

        $table->addWhereParameter($type);

        return ' AND `' . $tableName . '`.`type`=?';
    }

    private function getKeysWhere(mysqlTable $table, ?array $keys, string $tableName = 'hc_attribute'): string
    {
        if (empty($keys)) {
            return '';
        }

        array_walk($keys, function (string $key) use ($table) {
            $table->addWhereParameter($key);
        });

        return ' AND `' . $tableName . '`.`key` IN (' . $table->getParametersString($keys) . ')';
    }

    private function getSubIdWhere(mysqlTable $table, ?int $subId, string $tableName = 'hc_attribute'): string
    {
        if ($subId === null) {
            return ' AND `' . $tableName . '`.`sub_id` IS NULL';
        }

        $table->addWhereParameter($subId);

        return ' AND `' . $tableName . '`.`sub_id`=?';
    }
}
