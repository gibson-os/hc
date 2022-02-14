<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper;

interface AttributeMapperInterface
{
    public function mapToDatabase(int|float|string|null|bool|array|object $value): int|float|string|null|bool|array|object;

    public function mapFromDatabase(int|float|string|null|bool|array|object $value): int|float|string|null|bool|array|object;
}
