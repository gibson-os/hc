<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use DateTime;
use Exception;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Attribute as AttributeModel;
use GibsonOS\Module\Hc\Model\Attribute\Value as ValueModel;
use GibsonOS\Module\Hc\Model\Module as ModuleModel;

class AttributeRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     * @throws Exception
     *
     * @return AttributeModel[]
     */
    public function getByModule(
        ModuleModel $module,
        int $subId = null,
        string $key = null,
        string $type = null
    ): array {
        $table = self::getTable(AttributeModel::getTableName());

        $where =
            '`type_id`=' . $module->getTypeId() . ' AND ' .
            '`module_id`=' . $module->getId();

        if (null !== $subId) {
            $where .= ' AND `sub_id`=' . $subId;
        }

        if ($key !== null) {
            $where .= ' AND `key`=' . self::escape($key);
        }

        if ($type !== null) {
            $where .= ' AND `type`=' . self::escape($type);
        }

        $table->setWhere($where);

        if (!$table->select(false)) {
            throw new SelectError();
        }

        $models = [];

        foreach ($table->connection->fetchObjectList() as $attribute) {
            $models[] = (new AttributeModel())
                ->setId((int) $attribute->id)
                ->setTypeId(empty($attribute->type_id) ? null : (int) $attribute->type_id)
                ->setModuleId(empty($attribute->module_id) ? null : (int) $attribute->module_id)
                ->setSubId(empty($attribute->sub_id) ? null : (int) $attribute->sub_id)
                ->setKey($attribute->key)
                ->setType($attribute->type)
                ->setAdded(new DateTime($attribute->added))
            ;
        }

        return $models;
    }

    /**
     * @param string[] $values
     *
     * @throws DateTimeError
     * @throws SaveError
     * @throws GetError
     */
    public function addByModule(
        ModuleModel $module,
        array $values,
        int $subId = null,
        string $key = '',
        string $type = null
    ) {
        $attribute = (new AttributeModel())
            ->setModule($module)
            ->setType($type)
            ->setSubId($subId)
            ->setKey($key)
        ;
        $attribute->save();

        foreach ($values as $order => $value) {
            (new ValueModel())
                ->setAttribute($attribute)
                ->setValue($value)
                ->setOrder($order)
                ->save()
            ;
        }
    }

    public function countByModule(ModuleModel $module, string $type = null, int $subId = null): int
    {
        $table = self::getTable(AttributeModel::getTableName());

        $where =
            '`module_id`=' . self::escape((string) $module->getId()) . ' AND ' .
            '`type_id`=' . self::escape((string) $module->getTypeId())
        ;

        if ($type !== null) {
            $where .= ' AND `type`=' . self::escape($type);
        }

        if ($subId !== null) {
            $where .= ' AND `sub_id`=' . self::escape((string) $subId);
        }

        $table->setWhere($where);
        $count = $table->selectAggregate('COUNT(`id`)');

        return empty($count) ? 0 : (int) $count[0];
    }

    /**
     * @param int    $subId
     * @param string $key
     * @param string $type
     *
     * @throws DeleteError
     */
    public function deleteWithBiggerSubIds(
        ModuleModel $module,
        int $subId = null,
        string $key = null,
        string $type = null
    ) {
        $table = self::getTable(AttributeModel::getTableName());

        $where =
            '`type_id`=' . self::escape((string) $module->getTypeId()) . ' AND ' .
            '`module_id`=' . self::escape((string) $module->getId())
        ;

        if (null !== $subId) {
            $where .= ' AND `sub_id`>' . self::escape((string) $subId);
        }

        if (null !== $key) {
            $where .= ' AND `key`=' . self::escape($key);
        }

        if (null !== $type) {
            $where .= ' AND `type`=' . self::escape($type);
        }

        $table->setWhere($where);

        if (!$table->delete()) {
            throw new DeleteError();
        }
    }
}
