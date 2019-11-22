<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Attribute;

use DateTime;
use Exception;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Repository\UpdateError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Attribute as AttributeModel;
use GibsonOS\Module\Hc\Model\Attribute\Value as ValueModel;

class Value extends AbstractRepository
{
    /**
     * @param int         $typeId
     * @param int|null    $subId
     * @param int[]|null  $moduleIds
     * @param string|null $type
     * @param string|null $key
     * @param string|null $order
     *
     * @throws Exception
     *
     * @return ValueModel[]
     */
    public static function getByTypeId(
        int $typeId,
        ?int $subId = 0,
        ?array $moduleIds = [],
        ?string $type = '',
        string $key = null,
        string $order = null
    ): array {
        $where = '`hc_attribute`.`type_id`=' . self::escape((string) $typeId);
        $where .= self::getModuleIdWhere($moduleIds);
        $where .= self::getTypeWhere($type);
        $where .= self::getSubIdWhere($subId);

        if (!empty($key)) {
            $where .= ' AND `hc_attribute`.`key`=' . self::escape($key);
        }

        if (!empty($order)) {
            $where .= ' AND `hc_attribute_value`.`order`=' . self::escape($order);
        }

        $separator = '#_#^#_#';

        $table = self::getTable(AttributeModel::getTableName());
        $table->setWhere($where);
        $table->appendJoin('`hc_attribute_value`', '`hc_attribute`.`id`=`hc_attribute_value`.`attribute_id`');
        $table->setGroupBy('`hc_attribute`.`id`');
        $table->setOrderBy('`hc_attribute`.`id`, `hc_attribute_value`.`order`');

        $select =
            '`hc_attribute`.`id`, ' .
            '`hc_attribute`.`type_id`, ' .
            '`hc_attribute`.`module_id`, ' .
            '`hc_attribute`.`sub_id`, ' .
            '`hc_attribute`.`key`, ' .
            '`hc_attribute`.`type`, ' .
            '`hc_attribute`.`added`, ' .
            'GROUP_CONCAT(`value` SEPARATOR ' . self::escape($separator) . ') AS `values`, ' .
            'GROUP_CONCAT(`order` SEPARATOR ' . self::escape($separator) . ') AS `orders`';

        if (!$table->select(false, $select)) {
            return [];
        }

        $attributes = $table->connection->fetchObjectList();
        $models = [];

        foreach ($attributes as $attribute) {
            $attributeModel = (new AttributeModel())
                ->setId($attribute->id)
                ->setTypeId($attribute->type_id)
                ->setModuleId($attribute->module_id)
                ->setSubId($attribute->sub_id)
                ->setKey($attribute->key)
                ->setType($attribute->type)
                ->setAdded(new DateTime($attribute->added));

            $orders = explode($separator, $attribute->orders);

            foreach (explode($separator, $attribute->values) as $pos => $value) {
                $models[] = (new ValueModel())
                    ->setAttribute($attributeModel)
                    ->setOrder((int) $orders[$pos])
                    ->setValue($value);
            }
        }

        return $models;
    }

    /**
     * @param int         $subId
     * @param int         $typeId
     * @param int[]|null  $moduleIds
     * @param string|null $type
     * @param string      $key
     * @param string      $order
     *
     * @throws DeleteError
     * @throws SelectError
     */
    public static function deleteBySubId(
        int $subId,
        int $typeId,
        ?array $moduleIds = [],
        ?string $type = '',
        string $key = null,
        string $order = null
    ): void {
        $where =
            '`sub_id`=' . self::escape((string) $subId) . ' AND ' .
            '`type_id`=' . self::escape((string) $typeId);
        $where .= self::getModuleIdWhere($moduleIds);
        $where .= self::getTypeWhere($type);

        if (!empty($key)) {
            $where .= ' AND `key`=' . self::escape($key);
        }

        $table = self::getTable(AttributeModel::getTableName());
        $table->setWhere($where);

        if (!$table->select(false, '`id`')) {
            $exception = new SelectError();
            $exception->setTable($table);

            throw $exception;
        }

        $ids = $table->connection->fetchResultList();

        if (!count($ids)) {
            return;
        }

        $valueTable = self::getTable(ValueModel::getTableName());
        $where = '`attribute_id` IN (' . self::implode($ids) . ')';

        if (!empty($order)) {
            $where .= ' AND `order`=' . self::escape($order);
        }

        $valueTable->setWhere($where);

        if (!$valueTable->delete()) {
            $exception = new DeleteError('Werte konnten nicht gelöscht werden!');
            $exception->setTable($table);

            throw $exception;
        }
    }

    /**
     * @param int         $typeId
     * @param int         $startOrder
     * @param int         $updateOrder
     * @param array|null  $moduleIds
     * @param string|null $type
     * @param int|null    $subId
     * @param string|null $key
     *
     * @throws UpdateError
     */
    public static function updateOrder(
        int $typeId,
        int $startOrder,
        int $updateOrder,
        ?array $moduleIds = [],
        ?string $type = '',
        ?int $subId = 0,
        string $key = null
    ): void {
        $where = '`hc_attribute`.`type_id`=' . self::escape((string) $typeId);
        $where .= self::getModuleIdWhere($moduleIds);
        $where .= self::getTypeWhere($type);
        $where .= self::getSubIdWhere($subId);
        $where .= self::getKeysWhere([$key]);

        $attributeTable = self::getTable(AttributeModel::getTableName());
        $attributeTable->setWhere($where);

        $table = self::getTable(ValueModel::getTableName());
        $table->setWhere(
            '`order`>=' . self::escape((string) $startOrder) . ' AND ' .
            '`attribute_id` IN (' . mb_substr($attributeTable->getSelect('`id`'), 0, -1) . ')'
        );

        if (!$table->update('`order`=`order`+' . $updateOrder)) {
            $exception = new UpdateError();
            $exception->setTable($table);

            throw $exception;
        }
    }

    /**
     * @param string      $value
     * @param int         $typeId
     * @param array|null  $keys
     * @param array|null  $moduleIds
     * @param int|null    $subId
     * @param string|null $type
     *
     * @throws SelectError
     *
     * @return array
     */
    public static function findAttributesByValue(
        string $value,
        int $typeId,
        array $keys = null,
        ?array $moduleIds = [],
        ?int $subId = 0,
        ?string $type = ''
    ): array {
        $where =
            '`value`.`value` REGEXP ' . self::getRegexString($value) . ' AND ' .
            '`hc_attribute`.`type_id`=' . self::escape((string) $typeId);
        $where .= self::getModuleIdWhere($moduleIds);
        $where .= self::getSubIdWhere($subId);
        $where .= self::getTypeWhere($type);
        $where .= self::getKeysWhere($keys);

        $table = self::getTable(AttributeModel::getTableName());
        $table->setWhere($where);
        $table->appendJoin('`hc_attribute_value` AS `value`', '`hc_attribute`.`id`=`value`.`attribute_id`');
        $table->appendJoin(
            '`hc_attribute` AS `attribute`',
            'IF(`hc_attribute`.`sub_id` IS NULL, `hc_attribute`.`sub_id` IS NULL, `hc_attribute`.`sub_id`=`attribute`.`sub_id`) AND ' .
            '`hc_attribute`.`type_id`=`attribute`.`type_id` AND ' .
            'IF(`hc_attribute`.`type` IS NULL, `hc_attribute`.`type` IS NULL, `hc_attribute`.`type`=`attribute`.`type`) AND ' .
            'IF(`hc_attribute`.`module_id` IS NULL, `hc_attribute`.`module_id` IS NULL, `hc_attribute`.`module_id`=`attribute`.`module_id`)'
        );
        $table->appendJoin('`hc_attribute_value` AS `values`', '`attribute`.`id`=`values`.`attribute_id`');

        if (!$table->select(false, 'DISTINCT `attribute`.*, `values`.`value`, `values`.`order`')) {
            $exception = new SelectError('Keine Attribute gefunden!');
            $exception->setTable($table);

            throw $exception;
        }

        $models = [];
        $attributeModels = [];

        foreach ($table->connection->fetchObjectList() as $attribute) {
            if (!isset($attributeModels[$attribute->id])) {
                $attributeModels[$attribute->id] = (new AttributeModel())
                    ->setId($attribute->id)
                    ->setTypeId($attribute->type_id)
                    ->setModuleId($attribute->module_id)
                    ->setSubId($attribute->sub_id)
                    ->setKey($attribute->key)
                    ->setType($attribute->type)
                    ->setAdded(new DateTime($attribute->added))
                ;
            }

            $models[] = (new ValueModel())
                ->setAttribute($attributeModels[$attribute->id])
                ->setOrder($attribute->order)
                ->setValue($attribute->value)
            ;
        }

        return $models;
    }

    /**
     * @param string      $key
     * @param int         $typeId
     * @param array|null  $moduleIds
     * @param string|null $type
     *
     * @return int[]
     */
    public static function countByKey(string $key, int $typeId, ?array $moduleIds = [], ?string $type = ''): array
    {
        $where =
            '`hc_attribute`.`key`=' . self::escape($key) . ' AND ' .
            '`hc_attribute`.`type_id`=' . self::escape((string) $typeId);
        $where .= self::getModuleIdWhere($moduleIds);
        $where .= self::getTypeWhere($type);

        $table = self::getTable(AttributeModel::getTableName());
        $table->setWhere($where);
        $table->appendJoin('`hc_attribute_value` AS `value`', '`hc_attribute`.`id`=`value`.`attribute_id`');
        $table->setGroupBy('`value`.`value`');

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
     * @param array|null $moduleId
     * @param string     $table
     *
     * @return string
     */
    private static function getModuleIdWhere(?array $moduleId, string $table = 'hc_attribute'): string
    {
        if ($moduleId === null) {
            return ' AND `' . $table . '`.`module_id` IS NULL';
        }

        if (empty($moduleId)) {
            return '';
        }

        return ' AND `' . $table . '`.`module_id` IN (' . self::implode($moduleId) . ')';
    }

    /**
     * @param string|null $type
     * @param string      $table
     *
     * @return string
     */
    private static function getTypeWhere(?string $type, string $table = 'hc_attribute'): string
    {
        if ($type === null) {
            return ' AND `' . $table . '`.`type` IS NULL';
        }

        if (empty($type)) {
            return '';
        }

        return ' AND `' . $table . '`.`type`=' . self::escape($type);
    }

    /**
     * @param array|null $keys
     * @param string     $table
     *
     * @return string
     */
    private static function getKeysWhere(?array $keys, string $table = 'hc_attribute'): string
    {
        if ($keys === null) {
            return '';
        }

        return ' AND `' . $table . '`.`key` IN (' . self::implode($keys) . ')';
    }

    /**
     * @param int|null $subId
     * @param string   $table
     *
     * @return string
     */
    private static function getSubIdWhere(?int $subId, string $table = 'hc_attribute'): string
    {
        if ($subId === null) {
            return ' AND `' . $table . '`.`sub_id` IS NULL';
        }

        if ($subId === 0) {
            return '';
        }

        return ' AND `' . $table . '`.`sub_id`=' . self::escape((string) $subId);
    }
}
