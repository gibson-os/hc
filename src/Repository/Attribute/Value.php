<?php
namespace GibsonOS\Module\Hc\Repository\Attribute;

use DateTime;
use Exception;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Repository\UpdateError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Attribute as AttributeModel;
use GibsonOS\Module\Hc\Model\Attribute\Value as ValueModel;
use GibsonOS\Module\Hc\Model\Attribute;

class Value extends AbstractRepository
{
    /**
     * Gibt Werte anhand eine Sub ID zurück
     *
     * Gibt Werte anhand der Sub ID $subId zurück.
     *
     * @param int $typeId Modul Typen ID
     * @param bool $subId Sub ID
     * @param int|array|bool|null $moduleId Wenn false alle Module. Wenn null alle ohne Modul.
     * @param string|bool|null $type Wenn false alle Typen. Wenn null alle ohne Typen.
     * @param null|string $key
     * @param null|int $order
     * @return ValueModel[]
     * @throws Exception
     */
    public static function getByTypeId($typeId, $subId = false, $moduleId = false, $type = false, $key = null, $order = null): array
    {
        $where = '`hc_attribute`.`type_id`=' . self::escape($typeId);
        $where .= self::getModuleIdWhere($moduleId);
        $where .= self::getTypeWhere($type);
        $where .= self::getSubIdWhere($subId);

        if (!is_null($key)) {
            $where .= ' AND `hc_attribute`.`key`=' . self::escape($key);
        }

        if (!is_null($order)) {
            $where .= ' AND `hc_attribute_value`.`order`=' . self::escape($order);
        }

        $separator = '#_#^#_#';

        $table = self::getTable(AttributeModel::getTableName());
        $table->setWhere($where);
        $table->appendJoin('`hc_attribute_value`', '`hc_attribute`.`id`=`hc_attribute_value`.`attribute_id`');
        $table->setGroupBy("`hc_attribute`.`id`");
        $table->setOrderBy("`hc_attribute`.`id`, `hc_attribute_value`.`order`");

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
                    ->setOrder($orders[$pos])
                    ->setValue($value);
            }
        }

        return $models;
    }

    /**
     * @param $subId
     * @param $typeId
     * @param bool $moduleId
     * @param bool $type
     * @param null $key
     * @param null $order
     * @throws DeleteError
     * @throws SelectError
     */
    public static function deleteBySubId($subId, $typeId, $moduleId = false, $type = false, $key = null, $order = null)
    {
        $where =
            '`sub_id`=' . self::escape($subId) . ' AND ' .
            '`type_id`=' . self::escape($typeId);
        $where .= self::getModuleIdWhere($moduleId);
        $where .= self::getTypeWhere($type);

        if (!is_null($key)) {
            $where .= ' AND `key`=' . self::escape($key);
        }

        $table = self::getTable(AttributeModel::getTableName());
        $table->setWhere($where);

        if (!$table->select(false, "`id`")) {
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

        if (!is_null($order)) {
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
     * @param int $typeId
     * @param int $startOrder
     * @param int $updateOrder
     * @param int|array|bool|null $moduleId
     * @param int|array|bool|null $type
     * @param int|bool|null $subId
     * @param string|array|null $key
     * @throws UpdateError
     */
    public static function updateOrder($typeId, $startOrder, $updateOrder, $moduleId = false, $type = false, $subId = false, $key = null)
    {
        $where = '`hc_attribute`.`type_id`=' . self::escape($typeId);
        $where .= self::getModuleIdWhere($moduleId);
        $where .= self::getTypeWhere($type);
        $where .= self::getSubIdWhere($subId);
        $where .= self::getKeysWhere($key);

        $attributeTable = self::getTable(AttributeModel::getTableName());
        $attributeTable->setWhere($where);

        $table = self::getTable(ValueModel::getTableName());
        $table->setWhere(
            '`order`>=' . self::escape($startOrder) . ' AND ' .
            '`attribute_id` IN (' . mb_substr($attributeTable->getSelect('`id`'), 0, -1) . ')'
        );

        if (!$table->update('`order`=`order`+' . $updateOrder)) {
            $exception = new UpdateError();
            $exception->setTable($table);
            throw $exception;
        }
    }

    /**
     * Findet Attribute anhand eines Wertes
     *
     * Findet Attribute anhand des Wertes $value.
     *
     * @param string $value Wert
     * @param int $typeId Modul Typen ID
     * @param bool|array|null $keys Wenn null alle Schlüssel.
     * @param int|array|bool|null $moduleId Wenn false alle Module. Wenn null alle ohne Modul.
     * @param int|bool|null $subId Wenn false alle Sub IDs. Wenn null alle ohne Sub ID.
     * @param string|bool|null $type Wenn false alle Typen. Wenn null alle ohne Typen.
     * @return ValueModel[]
     * @throws SelectError
     * @throws Exception
     */
    public static function findAttributesByValue($value, $typeId, $keys = false, $moduleId = false, $subId = false, $type = false)
    {
        $where =
            "`value`.`value` REGEXP " . self::getRegexString($value) . " AND " .
            "`hc_attribute`.`type_id`=" . self::escape($typeId);
        $where .= self::getModuleIdWhere($moduleId);
        $where .= self::getSubIdWhere($subId);
        $where .= self::getTypeWhere($type);
        $where .= self::getKeysWhere($keys);

        $table = self::getTable(AttributeModel::getTableName());
        $table->setWhere($where);
        $table->appendJoin("`hc_attribute_value` AS `value`", "`hc_attribute`.`id`=`value`.`attribute_id`");
        $table->appendJoin(
            "`hc_attribute` AS `attribute`",
            "IF(`hc_attribute`.`sub_id` IS NULL, `hc_attribute`.`sub_id` IS NULL, `hc_attribute`.`sub_id`=`attribute`.`sub_id`) AND " .
            "`hc_attribute`.`type_id`=`attribute`.`type_id` AND " .
            "IF(`hc_attribute`.`type` IS NULL, `hc_attribute`.`type` IS NULL, `hc_attribute`.`type`=`attribute`.`type`) AND " .
            "IF(`hc_attribute`.`module_id` IS NULL, `hc_attribute`.`module_id` IS NULL, `hc_attribute`.`module_id`=`attribute`.`module_id`)"
        );
        $table->appendJoin("`hc_attribute_value` AS `values`", "`attribute`.`id`=`values`.`attribute_id`");

        if (!$table->select(false, "DISTINCT `attribute`.*, `values`.`value`, `values`.`order`")) {
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
                    ->setAdded(new DateTime($attribute->added));
            }

            $models[] = (new ValueModel())
                ->setAttribute($attributeModels[$attribute->id])
                ->setOrder($attribute->order)
                ->setValue($attribute->value);
        }

        return $models;
    }

    public static function countByKey(string $key, int $typeId, $moduleId = false, $type = false): array
    {
        $where =
            "`hc_attribute`.`key`=" . self::escape($key) . " AND " .
            "`hc_attribute`.`type_id`=" . self::escape($typeId);
        $where .= self::getModuleIdWhere($moduleId);
        $where .= self::getTypeWhere($type);

        $table = self::getTable(AttributeModel::getTableName());
        $table->setWhere($where);
        $table->appendJoin("`hc_attribute_value` AS `value`", "`hc_attribute`.`id`=`value`.`attribute_id`");
        $table->setGroupBy('`value`.`value`');

        if (!$table->select(false, "`value`.`value`, COUNT(DISTINCT `hc_attribute`.`id`) AS `count`")) {
            return [];
        }

        $counts = [];

        foreach ($table->connection->fetchObjectList() as $attribute) {
            $counts[$attribute->value] = $attribute->count;
        }

        return $counts;
    }

    /**
     * Gibt die SQL Where Bedingung für Modules zurück
     *
     * Gibt die SQL Where Bedingung für Modules $moduleId zurück.
     *
     * @param int|array|bool|null $moduleId Wenn false alle Module. Wenn null alle ohne Modul.
     * @param string $table MySQL Tabellen Präfix
     * @return null|string
     */
    private static function getModuleIdWhere($moduleId, $table = 'hc_attribute')
    {
        if (is_null($moduleId)) {
            return ' AND `' . $table . '`.`module_id` IS NULL';
        } else if (is_array($moduleId)) {
            return ' AND `' . $table . '`.`module_id` IN (' . self::implode($moduleId) . ')';
        } else if ($moduleId === false) {
            return null;
        } else {
            return ' AND `' . $table . '`.`module_id`=' . self::escape($moduleId);
        }
    }

    /**
     * Gibt die SQL Where Bedingung für Type zurück
     *
     * Gibt die SQL Where Bedingung für Type $type zurück.
     *
     * @param string|bool|null $type Wenn false alle Typen. Wenn null alle ohne Typen.
     * @param string $table MySQL Tabellen Präfix
     * @return null|string
     */
    private static function getTypeWhere($type, $table = 'hc_attribute')
    {
        if (is_null($type)) {
            return ' AND `' . $table . '`.`type` IS NULL';
        } else if ($type === false) {
            return null;
        } else {
            return ' AND `' . $table . '`.`type`=' . self::escape($type);
        }
    }

    /**
     * Gibt die SQL Where Bedingung für Schlüssel zurück
     *
     * Gibt die SQL Where Bedingung für die Schlüssel $keys zurück.
     *
     * @param string|array|null $keys Wenn null alle Schlüssel.
     * @param string $table MySQL Tabellen Präfix
     * @return null|string
     */
    private static function getKeysWhere($keys, $table = 'hc_attribute')
    {
        if (!$keys) {
            return null;
        } else if (is_array($keys)) {
            return " AND `" . $table . "`.`key` IN (" . self::implode($keys) . ")";
        } else {
            return " AND `" . $table . "`.`key`=" . self::escape($keys);
        }
    }

    /**
     * Gibt die SQL Where Bedingung für Sub IDs zurück
     *
     * Gibt die SQL Where Bedingung für Sub IDs $subId zurück.
     *
     * @param int|bool|null $subId Wenn false alle Sub IDs. Wenn null alle ohne Sub ID.
     * @param string $table MySQL Tabellen Präfix
     * @return null|string
     */
    private static function getSubIdWhere($subId, $table = 'hc_attribute')
    {
        if (is_null($subId)) {
            return " AND `" . $table . "`.`sub_id` IS NULL";
        } else if ($subId === false) {
            return null;
        } else {
            return " AND `" . $table . "`.`sub_id`=" . self::escape($subId);
        }
    }
}