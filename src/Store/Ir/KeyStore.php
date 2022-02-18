<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Ir;

use Generator;
use GibsonOS\Core\Attribute\GetSetting;
use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Mapper\ObjectMapper;
use GibsonOS\Core\Model\Setting;
use GibsonOS\Core\Service\DateTimeService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Formatter\IrFormatter;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Service\Slave\IrService;
use GibsonOS\Module\Hc\Store\AbstractAttributeStore;
use mysqlDatabase;

class KeyStore extends AbstractAttributeStore
{
    private array $irProtocols;

    /**
     * @param Setting $irProtocols
     * @param string  $valueTableName
     * @param string  $typeTableName
     */
    public function __construct(
        private IrFormatter $irFormatter,
        #[GetSetting('irProtocols')] Setting $irProtocols,
        DateTimeService $dateTimeService,
        ObjectMapper $objectMapper,
        #[GetTableName(Value::class)] string $valueTableName,
        #[GetTableName(Type::class)] string $typeTableName,
        mysqlDatabase $database = null
    ) {
        parent::__construct($dateTimeService, $objectMapper, $valueTableName, $typeTableName, $database);

        $this->setKeys([IrService::KEY_ATTRIBUTE_NAME]);
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

    protected function getDefaultOrder(): string
    {
        return '`' . $this->valueTableName . '`.`value`';
    }

    protected function getOrderMapping(): array
    {
        return [
            'name' => $this->getDefaultOrder(),
            'protocol' => '(`' . $this->tableName . '`.`sub_id` >> 32) & 255',
            //'protocolName' => //@todo per json table die protokolle injecten
            'address' => '(`' . $this->tableName . '`.`sub_id` >> 16) & 65535',
            'command' => '`' . $this->tableName . '`.`sub_id` & 65535',
        ];
    }

    protected function initTable(): void
    {
        parent::initTable();

        // @todo hier json table joinen
//        $this->table
//            ->appendJoinLeft(
//                '`' . $valueTableName . '`',
//                '`' . $tableName . '`.`id`=`' . $valueTableName . '`.`attribute_id`'
//            )
//            ->appendJoinLeft(
//                '`' . $typeTableName . '`',
//                '`' . $tableName . '`.`type_id`=`' . $typeTableName . '`.`id`'
//            )
//        ;
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
            yield $this->irFormatter->getKeyBySubId($attribute->getSubId() ?? 0)
                ->setName($attribute->getValues()[0]->getValue())
                ->setProtocolName($this->irProtocols[$attribute->getSubId() >> 32] ?? null)
            ;
        }
    }
}
