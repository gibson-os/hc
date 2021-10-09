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
use GibsonOS\Module\Hc\Model\Module as ModuleModel;

class ValueRepository extends AbstractRepository
{
    /**
     * @param int[]|null $moduleIds
     *
     * @throws Exception
     *
     * @return ValueModel[]
     */
    public function getByTypeId(
        int $typeId,
        ?int $subId,
        ?array $moduleIds = [],
        ?string $type = '',
        string $key = null,
        string $order = null
    ): array {
        $where = '`hc_attribute`.`type_id`=' . $this->escape((string) $typeId);
        $where .= $this->getModuleIdWhere($moduleIds);
        $where .= $this->getTypeWhere($type);
        $where .= $this->getSubIdWhere($subId);

        if (!empty($key)) {
            $where .= ' AND `hc_attribute`.`key`=' . $this->escape($key);
        }

        if (!empty($order)) {
            $where .= ' AND `hc_attribute_value`.`order`=' . $this->escape($order);
        }

        $separator = '#_#^#_#';

        $table = $this->getTable(AttributeModel::getTableName());
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
            'GROUP_CONCAT(`value` SEPARATOR ' . $this->escape($separator) . ') AS `values`, ' .
            'GROUP_CONCAT(`order` SEPARATOR ' . $this->escape($separator) . ') AS `orders`';

        if (!$table->select(false, $select)) {
            return [];
        }

        $attributes = $table->connection->fetchObjectList();
        $models = [];

        foreach ($attributes as $attribute) {
            $attributeModel = (new AttributeModel())
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
                $models[] = (new ValueModel())
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
     * @throws SelectError
     */
    public function deleteBySubId(
        int $subId,
        int $typeId,
        ?array $moduleIds = [],
        ?string $type = '',
        string $key = null,
        string $order = null
    ): void {
        $where =
            '`sub_id`=' . $this->escape((string) $subId) . ' AND ' .
            '`type_id`=' . $this->escape((string) $typeId);
        $where .= $this->getModuleIdWhere($moduleIds);
        $where .= $this->getTypeWhere($type);

        if (!empty($key)) {
            $where .= ' AND `key`=' . $this->escape($key);
        }

        $table = $this->getTable(AttributeModel::getTableName());
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

        $valueTable = $this->getTable(ValueModel::getTableName());
        $where = '`attribute_id` IN (' . $this->implode($ids) . ')';

        if (!empty($order)) {
            $where .= ' AND `order`=' . $this->escape($order);
        }

        $valueTable->setWhere($where);

        if (!$valueTable->delete()) {
            $exception = new DeleteError('Werte konnten nicht gelöscht werden!');
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
        $where =
            '`type_id`=' . $module->getTypeId() . ' AND ' .
            '`module_id`=' . $module->getId()
        ;
        $where .= $this->getTypeWhere($type);
        $where .= $this->getSubIdWhere($subId);
        $where .= $this->getKeysWhere($keys);

        $table = $this->getTable(AttributeModel::getTableName());
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

        $valueTable = $this->getTable(ValueModel::getTableName());
        $where = '`attribute_id` IN (' . $this->implode($ids) . ')';

        $valueTable->setWhere($where);

        if (!$valueTable->delete()) {
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
        $where = '`hc_attribute`.`type_id`=' . $this->escape((string) $typeId);
        $where .= $this->getModuleIdWhere($moduleIds);
        $where .= $this->getTypeWhere($type);
        $where .= $this->getSubIdWhere($subId);
        $where .= $this->getKeysWhere([$key]);

        $attributeTable = $this->getTable(AttributeModel::getTableName());
        $attributeTable->setWhere($where);

        $table = $this->getTable(ValueModel::getTableName());
        $table->setWhere(
            '`order`>=' . $this->escape((string) $startOrder) . ' AND ' .
            '`attribute_id` IN (' . mb_substr($attributeTable->getSelect('`id`'), 0, -1) . ')'
        );

        if (!$table->update('`order`=`order`+' . $updateOrder)) {
            $exception = new UpdateError();
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
        $where =
            '`value`.`value` REGEXP \'' . $this->getRegexString($value) . '\' AND ' .
            '`hc_attribute`.`type_id`=' . $this->escape((string) $typeId);
        $where .= $this->getModuleIdWhere($moduleIds);
        $where .= $this->getSubIdWhere($subId);
        $where .= $this->getTypeWhere($type);
        $where .= $this->getKeysWhere($keys);

        $table = $this->getTable(AttributeModel::getTableName());
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
     * @return int[]
     */
    public function countByKey(string $key, int $typeId, ?array $moduleIds = [], ?string $type = ''): array
    {
        $where =
            '`hc_attribute`.`key`=' . $this->escape($key) . ' AND ' .
            '`hc_attribute`.`type_id`=' . $this->escape((string) $typeId);
        $where .= $this->getModuleIdWhere($moduleIds);
        $where .= $this->getTypeWhere($type);

        $table = $this->getTable(AttributeModel::getTableName());
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

    private function getModuleIdWhere(?array $moduleId, string $table = 'hc_attribute'): string
    {
        if ($moduleId === null) {
            return ' AND `' . $table . '`.`module_id` IS NULL';
        }

        if (empty($moduleId)) {
            return '';
        }

        return ' AND `' . $table . '`.`module_id` IN (' . $this->implode($moduleId) . ')';
    }

    private function getTypeWhere(?string $type, string $table = 'hc_attribute'): string
    {
        if ($type === null) {
            return ' AND `' . $table . '`.`type` IS NULL';
        }

        if (empty($type)) {
            return '';
        }

        return ' AND `' . $table . '`.`type`=' . $this->escape($type);
    }

    private function getKeysWhere(?array $keys, string $table = 'hc_attribute'): string
    {
        if (empty($keys)) {
            return '';
        }

        return ' AND `' . $table . '`.`key` IN (' . $this->implode($keys) . ')';
    }

    private function getSubIdWhere(?int $subId, string $table = 'hc_attribute'): string
    {
        if ($subId === null) {
            return ' AND `' . $table . '`.`sub_id` IS NULL';
        }

        return ' AND `' . $table . '`.`sub_id`=' . $this->escape((string) $subId);
    }
}
