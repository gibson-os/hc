<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper;

use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Manager\ReflectionManager;
use GibsonOS\Core\Service\ServiceManagerService;
use GibsonOS\Module\Hc\Attribute\AttributeMapper as AttributeMapperAttribute;
use JsonSerializable;
use ReflectionAttribute;
use ReflectionException;

class AttributeMapper implements AttributeMapperInterface
{
    public function __construct(
        private ReflectionManager $reflectionManager,
        private ServiceManagerService $serviceManagerService
    ) {
    }

    /**
     * @throws FactoryError
     * @throws ReflectionException
     */
    public function mapToDatabase(float|object|array|bool|int|string|null $value): int|float|string|null|bool|array|object
    {
        if (!$value instanceof JsonSerializable) {
            return $value;
        }

        $reflectionClass = $this->reflectionManager->getReflectionClass($value);
        $newValue = $value->jsonSerialize();

        if (!is_array($newValue)) {
            return $newValue;
        }

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

            $attributeMapper = $this->serviceManagerService->get(
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
     */
    public function mapFromDatabase(float|object|array|bool|int|string|null $value): int|float|string|null|bool|array|object
    {
        return $this->map($value, fn (AttributeMapperInterface $mapper, $value) => $mapper->mapFromDatabase($value));
    }

    /**
     * @throws FactoryError
     * @throws ReflectionException
     */
    private function map(
        float|object|array|bool|int|string|null $value,
        callable $mapFunction
    ): int|float|string|null|bool|array|object {
        $newValues = is_array($value) ? $value : [$value];

        foreach ($newValues as &$newValue) {
            if (!is_object($newValue)) {
                continue;
            }

            $reflectionClass = $this->reflectionManager->getReflectionClass($newValue::class);

            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $attributeMapper = $this->reflectionManager->getAttribute(
                    $reflectionProperty,
                    AttributeMapperAttribute::class,
                    ReflectionAttribute::IS_INSTANCEOF
                );

                if ($attributeMapper !== null) {
                    $mapper = $this->serviceManagerService->get(
                        $attributeMapper->getAttributeMapper(),
                        AttributeMapperInterface::class
                    );
                    $newValue = $mapFunction($mapper, $newValue);
                }
            }
        }

        return is_array($value) ? $newValues : reset($newValues);
    }
}
