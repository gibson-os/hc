<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper\Ir;

use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Formatter\IrFormatter;
use GibsonOS\Module\Hc\Mapper\AttributeMapperInterface;
use GibsonOS\Module\Hc\Repository\AttributeRepository;

class RemoteKeyMapper implements AttributeMapperInterface
{
    public function __construct(private AttributeRepository $attributeRepository, private IrFormatter $irFormatter)
    {
    }

    public function mapToDatabase(float|object|array|bool|int|string|null $value): int
    {
        errlog($value);
        if (!$value instanceof Key) {
            throw new MapperException(sprintf(
                'Value for remote key mapper is no instance of "%s"!',
                Key::class
            ));
        }
        errlog($value->getSubId());

        return $value->getSubId();
    }

    public function mapFromDatabase(int|object|bool|array|float|string|null $value): Key
    {
        if (!is_int($value)) {
            throw new MapperException('Value for remote key mapper is no int!');
        }

        $key = $this->irFormatter->getKeyBySubId($value);
        $this->attributeRepository->loadDto($key);

        return $key;
    }
}
