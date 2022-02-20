<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper;

use ReflectionProperty;

interface AttributeMapperInterface
{
    public function mapToDatabase(int|float|string|null|bool|array|object $value): int|float|string|null|bool|array|object;

    public function mapFromDatabase(
        ReflectionProperty $reflectionProperty,
        int|float|string|null|bool|array|object $value
    ): int|float|string|null|bool|array|object;
}
