<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use Exception;
use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Model\DeleteError as ModelDeleteError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Mapper\ObjectMapper;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Core\Service\DateTimeService;
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
use ReflectionProperty;

class AttributeRepository extends AbstractRepository
{
    public function __construct(
        #[GetTableName(Attribute::class)] private string $attributeTableName,
        #[GetTableName(Value::class)] private string $valueTableName,
        private TypeRepository $typeRepository,
        private DateTimeService $dateTimeService,
        private ObjectMapper $objectMapper
    ) {
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
        } catch (SelectError) {
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
        $keyNames = [];
        $values = [];
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
                $reflectionAttributes = $reflectionProperty->getAttributes(IsAttribute::class);

                if (count($reflectionAttributes) === 0) {
                    continue;
                }

                /** @var IsAttribute $attributeAttribute */
                $attributeAttribute = $reflectionAttributes[0]->newInstance();
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
                $keyNames[$keyName] = $keyName;
                $values[$keyName] = $dto->$getter();

                /** @psalm-suppress UndefinedMethod */
                if ($reflectionProperty->getType()?->getName() !== 'array') {
                    $values[$keyName] = [$values[$keyName]];
                }
            }

            $attributes = $this->loadAttributes($type, $typeName, $keyNames, $subId);

            foreach ($attributes as $attribute) {
                unset($keyNames[$attribute->getKey()]);
            }

            foreach ($keyNames as $keyName) {
                $attribute = (new Attribute())
                    ->setType($typeName)
                    ->setTypeModel($type)
                    ->setSubId($subId)
                    ->setKey($keyName)
                    ->setModule($dto->getModule())
                ;
                $attribute->save();
                $attributes[] = $attribute;
            }

            foreach ($attributes as $attribute) {
                $key = $attribute->getKey();

                foreach ($attribute->getValues() as $value) {
                    if (!isset($values[$key][$value->getOrder()])) {
                        $value->delete();

                        continue;
                    }

                    $newValue = $values[$key][$value->getOrder()];
                    $value
                        ->setValue(is_array($newValue) || is_object($newValue) ? JsonUtility::encode($newValue) : (string) $newValue)
                        ->save()
                    ;
                    unset($values[$key][$value->getOrder()]);
                }

                foreach ($values[$key] as $order => $value) {
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

    /**
     * @template T of AttributeInterface
     *
     * @param T $dto
     *
     * @throws AttributeException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     * @throws MapperException
     *
     * @return T
     */
    public function loadDto(AttributeInterface $dto): AttributeInterface
    {
        $type = $this->typeRepository->getByHelperName($dto->getTypeName());
        $reflectionClass = new ReflectionClass($dto);
        /** @var array<string, array{setter: string, reflectionProperty: ReflectionProperty, attribute: IsAttribute}> $properties */
        $properties = [];

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $reflectionAttributes = $reflectionProperty->getAttributes(IsAttribute::class);

            if (count($reflectionAttributes) === 0) {
                continue;
            }

            /** @var IsAttribute $attributeAttribute */
            $attributeAttribute = $reflectionAttributes[0]->newInstance();

            $keyName = $attributeAttribute->getName() ?? $reflectionProperty->getName();
            $setter = 'set' . ucfirst($reflectionProperty->getName());

            if (!method_exists($dto, $setter)) {
                throw new AttributeException(sprintf(
                    'No setter found for property "%s" of dto "%s"!',
                    $reflectionProperty->getName(),
                    $dto::class
                ));
            }

            $properties[$keyName] = [
                'setter' => $setter,
                'reflectionProperty' => $reflectionProperty,
                'attribute' => $attributeAttribute,
            ];
        }

        foreach ($this->loadAttributes($type, $dto->getTypeName(), array_keys($properties), $dto->getSubId()) as $attribute) {
            if (count($attribute->getValues()) === 0) {
                continue;
            }

            $keyName = $attribute->getKey();
            $property = $properties[$keyName];
            /** @var ReflectionProperty $reflectionProperty */
            $reflectionProperty = $property['reflectionProperty'];
            /** @var IsAttribute $reflectionAttribute */
            $reflectionAttribute = $property['attribute'];
            /** @psalm-suppress UndefinedMethod */
            $typeName = $reflectionProperty->getType()?->getName();
            $propertyType = $reflectionAttribute->getType();
            $mappedValues = array_map(
                fn (Value $value) => match ($typeName) {
                    'int' => (int) $value->getValue(),
                    'float' => (float) $value->getValue(),
                    'bool' => (bool) $value->getValue(),
                    default => $propertyType === null
                        ? $value->getValue()
                        : $this->objectMapper->map($propertyType, JsonUtility::decode($value->getValue())),
                },
                $attribute->getValues()
            );

            if ($typeName === 'array') {
                $dto->{$property['setter']}($mappedValues);

                continue;
            }

            $dto->{$property['setter']}(reset($mappedValues));
        }

        return $dto;
    }

    /**
     * @param AttributeInterface[] $dtos
     */
    public function removeDtos(array $dtos): void
    {
        $this->deleteSubIds(array_map(fn (AttributeInterface $dto): int => $dto->getSubId() ?? 0, $dtos));
    }

    /**
     * @throws Exception
     *
     * @return Attribute[]
     */
    private function loadAttributes(Type $typeModel, string $type, array $keyNames, int $subId = null): array
    {
        $separator = '#_#^#_#';
        $table = $this->getTable($this->attributeTableName);
        $parameters = array_values($keyNames);
        array_push($parameters, $typeModel->getId() ?? 0, $type);
        $where =
            '`' . $this->attributeTableName . '`.`key` IN (' . $table->getParametersString($keyNames) . ') AND ' .
            '`' . $this->attributeTableName . '`.`type_id`=? AND ' .
            '`' . $this->attributeTableName . '`.`type`=? AND ' .
            '`' . $this->attributeTableName . '`.`sub_id`' . ($subId === null ? ' IS NULL' : '=?')
        ;

        if ($subId !== null) {
            $parameters[] = $subId;
        }

        $table
            ->setWhere($where)
            ->setWhereParameters($parameters)
            ->appendJoinLeft(
                $this->valueTableName,
                '`' . $this->attributeTableName . '`.`id`=`' . $this->valueTableName . '`.`attribute_id`'
            )
            ->setGroupBy('`' . $this->attributeTableName . '`.`id`')
            ->setOrderBy('`' . $this->valueTableName . '`.`order`')
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
            'GROUP_CONCAT(`order` SEPARATOR "' . $separator . '") AS `orders`'
        ;

        if (!$table->selectPrepared(false, $select)) {
            $exception = new SelectError($table->connection->error() ?: 'No results!');
            $exception->setTable($table);

            throw $exception;
        }

        $attributes = $table->connection->fetchObjectList();
        $models = [];

        foreach ($attributes as $attribute) {
            $orders = explode($separator, $attribute->orders);
            $values = explode($separator, $attribute->values);

            $models[] = (new Attribute())
                ->setId((int) $attribute->id)
                ->setTypeId(empty($attribute->type_id) ? null : (int) $attribute->type_id)
                ->setModuleId(empty($attribute->module_id) ? null : (int) $attribute->module_id)
                ->setSubId(empty($attribute->sub_id) ? null : (int) $attribute->sub_id)
                ->setKey($attribute->key)
                ->setType($attribute->type)
                ->setValues(array_map(
                    fn (string $value, string $order): Value => (new Value())
                        ->setValue($value)
                        ->setOrder((int) $order),
                    $values,
                    $orders
                ))
                ->setAdded($this->dateTimeService->get($attribute->added))
            ;
        }

        return $models;
    }
}
