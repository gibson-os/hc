<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Io;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Service\AttributeService;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Service\Slave\IoService as IoService;
use GibsonOS\Module\Hc\Store\AbstractAttributeStore;
use mysqlDatabase;

class DirectConnectStore extends AbstractAttributeStore
{
    public function __construct(
        private PortStore $portStore,
        AttributeService $attributeService,
        #[GetTableName(Value::class)] string $valueTableName,
        mysqlDatabase $database = null
    ) {
        parent::__construct($attributeService, $valueTableName, $database);
    }

    protected function getType(): string
    {
        return IoService::ATTRIBUTE_TYPE_DIRECT_CONNECT;
    }

    /**
     * @return array[]
     */
    public function getList(): array
    {
        $this->portStore->setModuleId($this->moduleId);
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
            if (isset($portsWithDirectConnect[$port['number']])) {
                continue;
            }

            $list[sprintf("%'.03d", $port['number']) . '000'] = $this->getInputElement($port);
        }

        ksort($list);

        return array_values($list);
    }

    public function setModuleId(?int $moduleId): DirectConnectStore
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    private function getInputElement(array $inputPort): array
    {
        return [
            'inputPort' => $inputPort['number'],
            'order' => 0,
            'inputPortName' => $inputPort[IoService::ATTRIBUTE_PORT_KEY_NAME],
            'valueNames' => $inputPort[IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES],
        ];
    }
}
