<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Module;

class AttributeRepository extends AbstractRepository
{
    public function __construct(#[GetTableName(Attribute::class)] private string $attributeTableName)
    {
    }

    /**
     * @throws SelectError
     *
     * @return Attribute[]
     */
    public function getByModule(
        Module $module,
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

        return $this->fetchAll($where, $parameters, Attribute::class);
    }

    /**
     * @param string[] $values
     *
     * @throws SaveError
     */
    public function addByModule(
        Module $module,
        array $values,
        int $subId = null,
        string $key = '',
        string $type = null
    ): void {
        $attribute = (new Attribute())
            ->setModule($module)
            ->setType($type)
            ->setSubId($subId)
            ->setKey($key)
        ;
        $attribute->save();

        foreach ($values as $order => $value) {
            (new Value())
                ->setAttribute($attribute)
                ->setValue($value)
                ->setOrder($order)
                ->save()
            ;
        }
    }

    public function countByModule(Module $module, string $type = null, int $subId = null): int
    {
        $where = '`module_id`=? AND `type_id`=?';
        $parameters = [$module->getId(), $module->getTypeId()];

        if ($type !== null) {
            $where .= ' AND `type`=?';
            $parameters[] = $type;
        }

        if ($subId !== null) {
            $where .= ' AND `sub_id`=?';
            $parameters[] = $subId;
        }

        $count = $this->getAggregate('COUNT(`id`)', $where, $parameters, Attribute::class);

        return empty($count) ? 0 : (int) $count[0];
    }

    public function deleteSubIds(array $ids): void
    {
        $table = self::getTable($this->attributeTableName);
        $table
            ->setWhere('`sub_id` IN (' . $table->getParametersString($ids) . ')')
            ->setWhereParameters($ids)
            ->deletePrepared()
        ;
    }

    /**
     * @throws DeleteError
     */
    public function deleteWithBiggerSubIds(
        Module $module,
        int $subId = null,
        string $key = null,
        string $type = null
    ): void {
        $table = self::getTable($this->attributeTableName);

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
