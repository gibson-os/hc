<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper\Ir;

use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Exception\AttributeException;
use GibsonOS\Module\Hc\Formatter\IrFormatter;
use GibsonOS\Module\Hc\Mapper\AttributeMapperInterface;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use JsonException;
use ReflectionException;
use ReflectionProperty;

class RemoteKeyMapper implements AttributeMapperInterface
{
    public function __construct(private AttributeRepository $attributeRepository, private IrFormatter $irFormatter)
    {
    }

    /**
     * @throws MapperException
     *
     * @return int[]
     */
    public function mapToDatabase(float|object|array|bool|int|string|null $value): array
    {
        $newValues = [];

        if (!is_array($value)) {
            throw new MapperException('Value for remote key mapper is no array!');
        }

        foreach ($value as $key) {
            if (!$key instanceof Key) {
                throw new MapperException(sprintf(
                    'Value for remote key mapper is no instance of "%s"!',
                    Key::class
                ));
            }

            $newValues[] = $key->getSubId();
        }

        return $newValues;
    }

    /**
     * @throws MapperException
     * @throws FactoryError
     * @throws SelectError
     * @throws AttributeException
     * @throws JsonException
     * @throws ReflectionException
     */
    public function mapFromDatabase(
        ReflectionProperty $reflectionProperty,
        int|bool|array|float|string|object|null $value
    ): array {
        $keys = [];

        if (!is_array($value)) {
            throw new MapperException('Value for remote key mapper is no array!');
        }

        foreach ($value as $subId) {
            if (!is_int($subId)) {
                throw new MapperException('Value for remote key mapper is no int!');
            }

            $key = $this->irFormatter->getKeyBySubId($subId);
            $this->attributeRepository->loadDto($key);
            $keys[] = $key->jsonSerialize();
        }

        return $keys;
    }
}
