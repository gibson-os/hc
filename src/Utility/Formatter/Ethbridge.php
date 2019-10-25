<?php
namespace GibsonOS\Module\Hc\Utility\Formatter;

use Exception;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Utility\Json;
use GibsonOS\Core\Service\ModuleSetting;
use GibsonOS\Module\Hc\Constant\Ethbridge as EthbridgeConstant;
use GibsonOS\Module\Hc\Repository\Attribute\Value;
use GibsonOS\Module\Hc\Service\Master;
use GibsonOS\Module\Hc\Utility\Transform;

class Ethbridge extends AbstractFormatter
{
    /**
     * @return null|string
     * @throws SelectError
     * @throws Exception
     */
    public function text(): ?string
    {
        if ($this->isDefaultType()) {
            return parent::text();
        }

        $data = $this->data;

        switch ($this->type) {
            case Master::TYPE_STATUS:
                return 'Status';
            case Master::TYPE_DATA:
                switch (Transform::hexToInt($data, 0)) {
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

                        $return .= 'Protokoll: ' . $irProtocols[$irData['protocol']] . '<br />'
                            . 'Adresse: ' . $irData['address'] . '<br />'
                            . 'Kommando: ' . $irData['command'];

                        return $return;
                    case EthbridgeConstant::DATA_TYPE_BRIDGE:
                        return 'bridge';
                }
        }

        return parent::text();
    }

    /**
     * Gibt IR Daten zurück
     *
     * Erzeugt aus einem Hex Datenstring ein Array mit den IR Daten.
     *
     * @return array|bool
     */
    public function getIrData()
    {
        $data = $this->data;

        if (Transform::hexToInt($data, 0) != EthbridgeConstant::DATA_TYPE_IR) {
            return false;
        }

        return [
            'protocol' => Transform::hexToInt($data, 1),
            'address' => Transform::hexToInt(substr($data, 4, 4)),
            'command' => Transform::hexToInt(substr($data, 8, 4))
        ];
    }

    /**
     * Gibt IR Taste zurück
     *
     * Gibt eine IR Taste zurück.
     *
     * @param int $protocol Protokol
     * @param int $address Adresse
     * @param int $command Kommando
     * @return array
     * @throws Exception
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