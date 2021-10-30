<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use Exception;
use GibsonOS\Core\Exception\DateTimeError;
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
     * @throws DateTimeError
     *
     * @return AttributeModel[]
     */
    public function getByModule(
        ModuleModel $module,
        int $subId = null,
        string $key = null,
        string $type = null
    ): array {
        $where = '`type_id`=? AND `module_id`=?';
        $parameters = [$module->getTypeId(), $module->getId()];

        if ($subId !== null) {
            $where .= ' AND `sub_id`=?';
            $parameters[] = $subId;
        }

        if ($key !== null) {
            $where .= ' AND `key`=?';
            $parameters[] = $key;
        }

        if ($type !== null) {
            $where .= ' AND `type`=?';
            $parameters[] = $type;
        }

        return $this->fetchAll($where, $parameters, AttributeModel::class);
    }

    /**
     * @param string[] $values
     *
     * @throws SaveError
     */
    public function addByModule(
        ModuleModel $module,
        array $values,
        int $subId = null,
        string $key = '',
        string $type = null
    ): void {
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

        $where = '`type_id`=? AND `module_id`=?';
        $table->setWhereParameters([$module->getTypeId(), $module->getId()]);

        if ($subId !== null) {
            $where .= ' AND `sub_id`>?';
            $table->addWhereParameter($subId);
        }

        if ($key !== null) {
            $where .= ' AND `key`=?';
            $table->addWhereParameter($key);
        }

        if ($type !== null) {
            $where .= ' AND `type`=?';
            $table->addWhereParameter($type);
        }

        $table->setWhere($where);

        if (!$table->deletePrepared()) {
            throw new DeleteError();
        }
    }
}
