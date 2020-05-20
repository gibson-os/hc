<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Io;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Service\Slave\IoService as IoService;

/**
 * Class Port.
 *
 * @package GibsonOS\Module\Hc\Store\Io
 */
class DirectConnectStore extends AbstractDatabaseStore
{
    private $moduleId;

    protected function getTableName(): string
    {
        return Attribute::getTableName();
    }

    /**
     * @return array[]
     */
    public function getList(): array
    {
        $portStore = new PortStore();
        $portStore->setModule($this->moduleId);
        $ports = $portStore->getList();

        $this->where[] = '`' . $this->getTableName() . '`.`type`=' . $this->database->escape(IoService::ATTRIBUTE_TYPE_DIRECT_CONNECT);

        $this->table->appendJoinLeft(
            '`gibson_os`.`hc_attribute_value`',
            '`' . $this->getTableName() . '`.`id`=`hc_attribute_value`.`attribute_id`'
        );

        $this->table->setWhere($this->getWhere());
        $this->table->setOrderBy('`hc_attribute`.`sub_id` ASC, `hc_attribute_value`.`order`');

        $this->table->select(
            false,
            '`' . $this->getTableName() . '`.`id`, ' .
            '`' . $this->getTableName() . '`.`sub_id`, ' .
            '`' . $this->getTableName() . '`.`key`, ' .
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

    public function getCountField(): string
    {
        return '`hc_attribute`.`sub_id`';
    }

    public function setModule(int $moduleId): DirectConnectStore
    {
        if ($moduleId === 0) {
            unset($this->where['moduleId']);
        } else {
            $this->where['moduleId'] = '`hc_attribute`.`module_id`=' . $moduleId;
        }

        $this->moduleId = $moduleId;

        return $this;
    }

    /**
     * @param array $inputPort
     */
    private function getInputElement($inputPort): array
    {
        return [
            'inputPort' => $inputPort['number'],
            'order' => 0,
            'inputPortName' => $inputPort[IoService::ATTRIBUTE_PORT_KEY_NAME],
            'valueNames' => $inputPort[IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES],
        ];
    }

    /**
     * @return string[]
     */
    protected function getOrderMapping(): array
    {
        return [];
    }
}
