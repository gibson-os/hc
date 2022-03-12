<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Io;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Mapper\ObjectMapper;
use GibsonOS\Core\Service\DateTimeService;
use GibsonOS\Module\Hc\Dto\Io\Port;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use GibsonOS\Module\Hc\Store\AbstractAttributeStore;
use JsonException;
use mysqlDatabase;
use ReflectionException;

class PortStore extends AbstractAttributeStore
{
    public function __construct(
        DateTimeService $dateTimeService,
        ObjectMapper $objectMapper,
        #[GetTableName(Value::class)] string $valueTableName,
        #[GetTableName(Type::class)] string $typeTableName,
        mysqlDatabase $database = null
    ) {
        parent::__construct($dateTimeService, $objectMapper, $valueTableName, $typeTableName, $database);
    }

    protected function getType(): string
    {
        return IoService::ATTRIBUTE_TYPE_PORT;
    }

    protected function getTypeName(): string
    {
        return 'io';
    }

    /**
     * @throws SelectError
     * @throws FactoryError
     * @throws MapperException
     * @throws JsonException
     * @throws ReflectionException
     *
     * @return Port[]
     */
    public function getList(): array
    {
        $list = [];

        /** @var Attribute $attribute */
        foreach (parent::getList() as $attribute) {
            $subId = $attribute->getSubId() ?? 0;
            $key = $attribute->getKey();

            if (!isset($list[$subId])) {
                $list[$subId] = [
                    'number' => $subId,
                    'module' => $this->module,
                ];
            }

            foreach ($attribute->getValues() as $value) {
                if ($key === IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES) {
                    if (!isset($list[$subId][$key]) || !is_array($list[$subId][$key])) {
                        $list[$subId][$key] = [];
                    }

                    $list[$subId][$key][$value->getOrder()] = $value->getValue();

                    continue;
                }

                $list[$subId][$key] = $value->getValue();
            }
        }

        $ports = [];

        foreach ($list as $port) {
            $ports[] = $this->objectMapper->mapToObject(Port::class, $port);
        }

        return $ports;
    }
}
