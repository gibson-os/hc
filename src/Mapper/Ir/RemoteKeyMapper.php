<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper\Ir;

use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Mapper\ObjectMapperInterface;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Exception\AttributeException;
use GibsonOS\Module\Hc\Formatter\IrFormatter;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use JsonException;
use ReflectionException;

class RemoteKeyMapper implements ObjectMapperInterface
{
    public function __construct(private AttributeRepository $attributeRepository, private IrFormatter $irFormatter)
    {
    }

    /**
     * @throws FactoryError
     * @throws MapperException
     * @throws SelectError
     * @throws AttributeException
     * @throws JsonException
     * @throws ReflectionException
     */
    public function mapToObject(string $className, array $properties): object
    {
        $key = new $className($properties['protocol'], $properties['address'], $properties['command']);

        if (!$key instanceof Key) {
            throw new MapperException(sprintf('Class "%s" is not "%s', $key::class, Key::class));
        }

        $this->attributeRepository->loadDto($key);

        return $key;
    }

    public function mapFromObject(object $object): int|float|string|bool|array|object|null
    {
        return null;
    }
}
