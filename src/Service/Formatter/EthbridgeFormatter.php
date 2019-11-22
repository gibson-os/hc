<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Formatter;

use Exception;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Json;
use GibsonOS\Core\Service\ModuleSetting;
use GibsonOS\Module\Hc\Constant\Ethbridge as EthbridgeConstant;
use GibsonOS\Module\Hc\Repository\Attribute\Value;
use GibsonOS\Module\Hc\Service\MasterService;

class EthbridgeFormatter extends AbstractFormatter
{
    /**
     * @throws SelectError
     * @throws Exception
     *
     * @return string|null
     */
    public function text(): ?string
    {
        if ($this->isDefaultType()) {
            return parent::text();
        }

        $data = $this->data;

        switch ($this->type) {
            case MasterService::TYPE_STATUS:
                return 'Status';
            case MasterService::TYPE_DATA:
                switch ($this->transform->hexToInt($data, 0)) {
                    case EthbridgeConstant::DATA_TYPE_IR:
                        $moduleSetting = ModuleSetting::getInstance();
                        $irProtocols = Json::decode(
                            $moduleSetting->getByRegistry('ethbridgeIrProtocols')->getValue(),
                            true
                        );
                        $irData = $this->getIrData();

                        $irKey = $this->getIrKey($irData['protocol'], $irData['address'], $irData['command']);
                        $return = '';

                        if (count($irKey)) {
                            $return = '<strong>' . $irKey['name'] . '</strong><br />';
                        }

                        $return .=
                            'Protokoll: ' . $irProtocols[$irData['protocol']] . '<br />' .
                            'Adresse: ' . $irData['address'] . '<br />' .
                            'Kommando: ' . $irData['command']
                        ;

                        return $return;
                    case EthbridgeConstant::DATA_TYPE_BRIDGE:
                        return 'bridge';
                }
        }

        return parent::text();
    }

    /**
     * Gibt IR Daten zurück.
     *
     * Erzeugt aus einem Hex Datenstring ein Array mit den IR Daten.
     *
     * @return array|bool
     */
    public function getIrData()
    {
        $data = $this->data;

        if ($this->transform->hexToInt($data, 0) != EthbridgeConstant::DATA_TYPE_IR) {
            return false;
        }

        return [
            'protocol' => $this->transform->hexToInt($data, 1),
            'address' => $this->transform->hexToInt(substr($data, 4, 4)),
            'command' => $this->transform->hexToInt(substr($data, 8, 4)),
        ];
    }

    /**
     * Gibt IR Taste zurück.
     *
     * Gibt eine IR Taste zurück.
     *
     * @param int $protocol Protokol
     * @param int $address  Adresse
     * @param int $command  Kommando
     *
     * @throws Exception
     *
     * @return array
     */
    public function getIrKey($protocol, $address, $command)
    {
        $subId = $protocol . $address . $command;

        $valueModels = Value::getByTypeId(
            $this->module->getTypeId(),
            $subId,
            false,
            EthbridgeConstant::ATTRIBUTE_TYPE_IR_KEY
        );
        $data = [];

        foreach ($valueModels as $valueModel) {
            $data[$valueModel->getAttribute()->getKey()] = $valueModel->getValue();
        }

        return $data;
    }
}
