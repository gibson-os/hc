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
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use GibsonOS\Module\Hc\Store\AbstractAttributeStore;
use JsonException;
use mysqlDatabase;
use ReflectionException;

class DirectConnectStore extends AbstractAttributeStore
{
    public function __construct(
        private PortStore $portStore,
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
        return IoService::ATTRIBUTE_TYPE_DIRECT_CONNECT;
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
     * @return array[]
     */
    public function getList(): array
    {
        $this->portStore->setModule($this->module);
        $ports = $this->portStore->getList();

        $this->initTable();
        $this->table->setOrderBy('`hc_attribute`.`sub_id` ASC, `hc_attribute_value`.`order`');

        $this->table->selectPrepared(
            false,
            '`hc_attribute`.`id`, ' .
            '`hc_attribute`.`sub_id`, ' .
            '`hc_attribute`.`key`, ' .
            'IFNULL(`hc_attribute_value`.`order`, 0) as `order`, ' .
            '`hc_attribute_value`.`value`'
        );

        $list = [];
        $portsWithDirectConnect = [];

        foreach ($this->table->connection->fetchObjectList() as $attribute) {
            $key =
                sprintf("%'.03d", $attribute->sub_id) .
                sprintf("%'.03d", $attribute->order);

            if (!isset($list[$key])) {
                $list[$key] = $this->getInputElement($ports[$attribute->sub_id]);
                $list[$key]['order'] = $attribute->order;
            }

            $list[$key][$attribute->key] = $attribute->value;
            $portsWithDirectConnect[$attribute->sub_id] = true;
        }

        foreach ($ports as $port) {
            if (isset($portsWithDirectConnect[$port->getNumber()])) {
                continue;
            }

            $list[sprintf("%'.03d", $port->getNumber()) . '000'] = $this->getInputElement($port);
        }

        ksort($list);

        return array_values($list);
    }

    private function getInputElement(Port $inputPort): array
    {
        return [
            'inputPort' => $inputPort->getNumber(),
            'order' => 0,
            'inputPortName' => $inputPort->getName(),
            'valueNames' => $inputPort->getValueNames(),
        ];
    }
}
