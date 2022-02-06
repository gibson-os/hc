<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use Exception;
use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Model\DeleteError as ModelDeleteError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Attribute\IsAttribute;
use GibsonOS\Module\Hc\Dto\AttributeInterface;
use GibsonOS\Module\Hc\Exception\AttributeException;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use JsonException;
use ReflectionClass;
use ReflectionException;

class AttributeRepository extends AbstractRepository
{
    public function __construct(
        #[GetTableName(Attribute::class)] private string $attributeTableName,
        private TypeRepository $typeRepository
    ) {
    }

    /**
     * @throws SelectError
     *
     * @return Attribute[]
     */
    public function getByType(
        Type $typeModel,
        int $subId = null,
        string $key = null,
        string $type = null
    ): array {
        $where = '`type_id`=?';
        $parameters = [$typeModel->getId()];

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
     * @throws ReflectionException
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

    public function getMaxSubId(Type $typeModel, string $type = null, Module $module = null): int
    {
        $where = '`type_id`=?';
        $parameters = [$typeModel->getId()];

        if ($module !== null) {
            $where .= ' AND `module_id`=?';
            $parameters[] = $module->getId();
        }

        if ($type !== null) {
            $where .= ' AND `type`=?';
            $parameters[] = $type;
        }

        try {
            $attribute = $this->fetchOne($where, $parameters, Attribute::class, '`sub_id` DESC');
        } catch (SelectError $e) {
            return 0;
        }

        return $attribute->getSubId() ?? 0;
    }

    /**
     * @throws AttributeException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws ModelDeleteError
     */
    public function saveDto(AttributeInterface $dto): void
    {
        $getterPrefixes = ['get', 'is', 'has'];
        $reflectionClass = new ReflectionClass($dto);
        $this->startTransaction();

        try {
            $type = $this->typeRepository->getByHelperName($dto->getTypeName());
            $typeName = lcfirst($reflectionClass->getShortName());
            $subId = $dto->getSubId() ?? (
                $dto::SUB_ID_NULLABLE
                ? null
                : ($this->getMaxSubId($type, $typeName, $dto->getModule()) + 1)
            );
            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $attributes = $reflectionProperty->getAttributes(IsAttribute::class);

                if (count($attributes) === 0) {
                    continue;
                }

                /** @var IsAttribute $attributeAttribute */
                $attributeAttribute = $attributes[0]->newInstance();
                $getter = null;
                $propertyName = ucfirst($reflectionProperty->getName());

                foreach ($getterPrefixes as $getterPrefix) {
                    if (!method_exists($dto, $getterPrefix . $propertyName)) {
                        continue;
                    }

                    $getter = $getterPrefix . $propertyName;

                    break;
                }

                if ($getter === null) {
                    throw new AttributeException(sprintf('No getter found for property "%"!', $reflectionProperty->getName()));
                }

                $keyName = $attributeAttribute->getName() ?? $reflectionProperty->getName();

                try {
                    $parameters = [$keyName];

                    if ($dto->getSubId() !== null) {
                        $parameters[] = $dto->getSubId();
                    }

                    $attribute = $this->fetchOne(
                        '`key`=? AND `sub_id`' . ($dto->getSubId() === null ? ' IS NULL' : '=?'),
                        $parameters,
                        Attribute::class
                    );
                } catch (SelectError) {
                    $attribute = new Attribute();
                }

                $attribute
                    ->setType($typeName)
                    ->setTypeModel($type)
                    ->setSubId($subId)
                    ->setKey($keyName)
                    ->setModule($dto->getModule())
                    ->save()
                ;

                $values = $dto->$getter();

                /** @psalm-suppress UndefinedMethod */
                if ($reflectionProperty->getType()?->getName() !== 'array') {
                    $values = [$values];
                }

                foreach ($attribute->getValues() as $value) {
                    if (!isset($values[$value->getOrder()])) {
                        $value->delete();

                        continue;
                    }

                    $newValue = $values[$value->getOrder()];
                    $value
                        ->setValue(is_array($newValue) || is_object($newValue) ? JsonUtility::encode($newValue) : (string) $newValue)
                        ->save()
                    ;
                    unset($values[$value->getOrder()]);
                }

                foreach ($values as $order => $value) {
                    (new Value())
                        ->setAttribute($attribute)
                        ->setValue(is_array($value) || is_object($value) ? JsonUtility::encode($value) : (string) $value)
                        ->setOrder($order)
                        ->save()
                    ;
                }
            }
        } catch (Exception $exception) {
            $this->rollback();

            throw $exception;
        }

        $this->commit();
    }
}
