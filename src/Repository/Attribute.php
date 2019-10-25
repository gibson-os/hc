<?php
namespace GibsonOS\Module\Hc\Repository;

use DateTime;
use Exception;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Attribute as AttributeModel;
use GibsonOS\Module\Hc\Model\Attribute\Value as ValueModel;
use GibsonOS\Module\Hc\Model\Module as ModuleModel;

class Attribute extends AbstractRepository
{
    /**
     * @param ModuleModel $module
     * @param null|int $subId
     * @param null|string $key
     * @param null|string $type
     * @return AttributeModel[]
     * @throws SelectError
     * @throws Exception
     */
    static function getByModule(ModuleModel $module, $subId = null, $key = null, $type = null): array
    {
        $table = self::getTable(AttributeModel::getTableName());

        $where =
            '`type_id`=' . self::escape($module->getTypeId()) . ' AND ' .
            '`module_id`=' . self::escape($module->getId());

        if (!is_null($subId)) {
            $where .= ' AND `sub_id`=' . self::escape($subId);
        }

        if (!is_null($key)) {
            $where .= ' AND `key`=' . self::escape($key);
        }

        if (!is_null($type)) {
            $where .= ' AND `type`=' . self::escape($type);
        }

        $table->setWhere($where);

        if (!$table->select(false)) {
            throw new SelectError();
        }

        $models = [];

        foreach ($table->connection->fetchObjectList() as $attribute) {
            $models[] = (new AttributeModel())
                ->setId($attribute->id)
                ->setTypeId($attribute->type_id)
                ->setModuleId($attribute->module_id)
                ->setSubId($attribute->sub_id)
                ->setKey($attribute->key)
                ->setType($attribute->type)
                ->setAdded(new DateTime($attribute->added));
        }

        return $models;
    }

    /**
     * @param ModuleModel $module
     * @param array $values
     * @param null|int $subId
     * @param null|string $key
     * @param null|string $type
     * @throws SaveError
     */
    static function addByModule(ModuleModel $module, $values, $subId = null, $key = null, $type = null)
    {
        $attribute = (new AttributeModel())
            ->setModule($module)
            ->setType($type)
            ->setSubId($subId)
            ->setKey($key);
        $attribute->save();

        foreach ($values as $order => $value) {
            (new ValueModel())
                ->setAttribute($attribute)
                ->setValue($value)
                ->setOrder($order)
                ->save();
        }
    }

    /**
     * @param ModuleModel $module
     * @param null $type
     * @param null $subId
     * @return int
     */
    public static function countByModule(ModuleModel $module, $type = null, $subId = null): int
    {
        $table = self::getTable(AttributeModel::getTableName());

        $where =
            '`module_id`=' . self::escape($module->getId()) . ' AND ' .
            '`type_id`=' . self::escape($module->getTypeId());

        if (!is_null($type)) {
            $where .= ' AND `type`=' . self::escape($type);
        }

        if (!is_null($subId)) {
            $where .= ' AND `sub_id`=' . self::escape($subId);
        }

        $table->setWhere($where);
        $count = $table->selectAggregate('COUNT(`id`)');

        return (int)$count[0];
    }

    /**
     * @param ModuleModel $module
     * @param null $subId
     * @param null $key
     * @param null $type
     * @throws DeleteError
     */
    public static function deleteWithBiggerSubIds(ModuleModel $module, $subId = null, $key = null, $type = null)
    {
        $table = self::getTable(AttributeModel::getTableName());

        $where =
            '`type_id`=' . self::escape($module->getTypeId()) . ' AND ' .
            '`module_id`=' . self::escape($module->getId());

        if (!is_null($subId)) {
            $where .= ' AND `sub_id`>' . self::escape($subId);
        }

        if (!is_null($key)) {
            $where .= ' AND `key`=' . self::escape($key);
        }

        if (!is_null($type)) {
            $where .= ' AND `type`=' . self::escape($type);
        }

        $table->setWhere($where);

        if (!$table->delete()) {
            throw new DeleteError();
        }
    }
}