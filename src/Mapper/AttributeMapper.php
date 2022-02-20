<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper;

use GibsonOS\Core\Attribute\ObjectMapper;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Manager\ReflectionManager;
use GibsonOS\Core\Manager\ServiceManager;
use GibsonOS\Module\Hc\Attribute\AttributeMapper as AttributeMapperAttribute;
use JsonSerializable;
use ReflectionAttribute;
use ReflectionException;
use ReflectionProperty;

class AttributeMapper implements AttributeMapperInterface
{
    public function __construct(
        private ReflectionManager $reflectionManager,
        private ServiceManager $serviceManager
    ) {
    }

    /**
     * @throws FactoryError
     * @throws ReflectionException
     */
    public function mapToDatabase(float|object|array|bool|int|string|null $value): int|float|string|null|bool|array|object
    {
        if (!$value instanceof JsonSerializable) {
            if (is_object($value) && enum_exists($value::class)) {
                return $value->name;
            }

            return $value;
        }

        $newValue = $value->jsonSerialize();

        if (!is_array($newValue)) {
            return $newValue;
        }

        $reflectionClass = $this->reflectionManager->getReflectionClass($value);

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $attributeMapperAttribute = $this->reflectionManager->getAttribute(
                $reflectionProperty,
                AttributeMapperAttribute::class,
                ReflectionAttribute::IS_INSTANCEOF
            );

            $propertyName = $reflectionProperty->getName();

            if (!array_key_exists($propertyName, $newValue)) {
                continue;
            }

            $propertyValue = $this->reflectionManager->getProperty($reflectionProperty, $value);

            if ($attributeMapperAttribute === null) {
                $newValue[$propertyName] = $this->mapToDatabase($propertyValue);

                continue;
            }

            $attributeMapper = $this->serviceManager->get(
                $attributeMapperAttribute->getAttributeMapper(),
                AttributeMapperInterface::class
            );

            $newValue[$propertyName] = $attributeMapper->mapToDatabase($propertyValue);
        }

        return $newValue;
    }

    /**
     * @throws FactoryError
     * @throws ReflectionException
     * @throws MapperException
     */
    public function mapFromDatabase(
        ReflectionProperty $reflectionProperty,
        float|array|bool|int|string|object|null $value
    ): int|float|string|null|bool|array|object {
        if (!is_array($value)) {
            if (is_object($value) && enum_exists($value::class)) {
                return $value->value;
            }

            return $value;
        }

        $objectMapper = $this->reflectionManager->getAttribute($reflectionProperty, ObjectMapper::class);

        if ($objectMapper === null) {
            return $value;
        }

        /** @psalm-suppress UndefinedMethod */
        $objectClassName = $objectMapper->getObjectClassName() ?? $reflectionProperty->getType()?->getName();

        if ($objectClassName === null) {
            throw new MapperException(sprintf(
                'No mapper object class name found for property "%s" of class "%s"',
                $reflectionProperty->getName(),
                $reflectionProperty->getDeclaringClass()->getName()
            ));
        }

        $reflectionClass = $this->reflectionManager->getReflectionClass($objectClassName);
        $newValues = [];

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $attributeMapperAttribute = $this->reflectionManager->getAttribute(
                $reflectionProperty,
                AttributeMapperAttribute::class,
                ReflectionAttribute::IS_INSTANCEOF
            );
            $mapper = $this;

            if ($attributeMapperAttribute !== null) {
                $mapper = $this->serviceManager->get($attributeMapperAttribute->getAttributeMapper());
            }

            $propertyName = $reflectionProperty->getName();

            if (!array_key_exists($propertyName, $value)) {
                continue;
            }

            $newValues[$propertyName] = $mapper->mapFromDatabase($reflectionProperty, $value[$propertyName]);
        }

        return $newValues;
    }
}
