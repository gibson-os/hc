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
