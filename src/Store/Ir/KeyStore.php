<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Ir;

use Generator;
use GibsonOS\Core\Attribute\GetSetting;
use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\CreateError;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\Setting;
use GibsonOS\Core\Service\AttributeService;
use GibsonOS\Core\Service\DateTimeService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Service\Slave\IrService;
use GibsonOS\Module\Hc\Store\AbstractAttributeStore;
use JsonException;
use mysqlDatabase;
use ReflectionException;

class KeyStore extends AbstractAttributeStore
{
    private array $irProtocols;

    /**
     * @param Setting $irProtocols
     * @param string  $valueTableName
     * @param string  $typeTableName
     *
     * @throws CreateError
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     */
    public function __construct(
        #[GetSetting('irProtocols')] Setting $irProtocols,
        DateTimeService $dateTimeService,
        AttributeService $attributeService,
        #[GetTableName(Value::class)] string $valueTableName,
        #[GetTableName(Type::class)] string $typeTableName,
        mysqlDatabase $database = null
    ) {
        parent::__construct($dateTimeService, $attributeService, $valueTableName, $typeTableName, $database);

        $this->setKey('name');
        $this->irProtocols = JsonUtility::decode($irProtocols->getValue());
    }

    protected function getType(): string
    {
        return IrService::ATTRIBUTE_TYPE_KEY;
    }

    protected function getTypeName(): string
    {
        return 'ir';
    }

    protected function getCountField(): string
    {
        return '`' . $this->tableName . '`.`sub_id`';
    }

    /**
     * @throws SelectError
     *
     * @return Generator<Key>
     */
    public function getList(): iterable
    {
        /** @var Attribute $attribute */
        foreach (parent::getList() as $attribute) {
            yield new Key(
                $attribute->getSubId() >> 32,
                ($attribute->getSubId() >> 16) & 0xFFFF,
                $attribute->getSubId() & 0xFFFF,
                $attribute->getValues()[0]->getValue(),
                $this->irProtocols[$attribute->getSubId() >> 32] ?? null
            );
        }
    }
}
