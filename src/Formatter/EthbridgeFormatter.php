<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use Exception;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\Setting;
use GibsonOS\Core\Service\ModuleSettingService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Constant\Ethbridge as EthbridgeConstant;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;

class EthbridgeFormatter extends AbstractFormatter
{
    private ModuleSettingService $moduleSetting;

    private ValueRepository $valueRepository;

    public function __construct(
        TransformService $transformService,
        ModuleSettingService $moduleSetting,
        ValueRepository $valueRepository
    ) {
        parent::__construct($transformService);
        $this->transformService = $transformService;
        $this->moduleSetting = $moduleSetting;
        $this->valueRepository = $valueRepository;
    }

    /**
     * @throws SelectError
     * @throws Exception
     */
    public function text(Log $log): ?string
    {
        if ($this->isDefaultType($log)) {
            return parent::text($log);
        }

        $data = $log->getData();

        switch ($log->getType()) {
            case MasterService::TYPE_STATUS:
                return 'Status';
            case MasterService::TYPE_DATA:
                switch ($this->transformService->hexToInt($data, 0)) {
                    case EthbridgeConstant::DATA_TYPE_IR:
                        $irProtocols = $this->moduleSetting->getByRegistry('ethbridgeIrProtocols');

                        if (!$irProtocols instanceof Setting) {
                            throw new GetError('Protokolle konnten nicht geladen werden!');
                        }

                        $irProtocols = JsonUtility::decode($irProtocols->getValue());
                        $irData = $this->getIrData($log);

                        if (empty($irData)) {
                            return '';
                        }

                        $irKey = $this->getIrKey(
                            $log,
                            $irData['protocol'],
                            $irData['address'],
                            $irData['command']
                        );
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

        return parent::text($log);
    }

    /**
     * @return int[]|null
     */
    private function getIrData(Log $log): ?array
    {
        $data = $log->getData();

        if ($this->transformService->hexToInt($data, 0) !== EthbridgeConstant::DATA_TYPE_IR) {
            return null;
        }

        return [
            'protocol' => $this->transformService->hexToInt($data, 1),
            'address' => $this->transformService->hexToInt(substr($data, 4, 4)),
            'command' => $this->transformService->hexToInt(substr($data, 8, 4)),
        ];
    }

    /**
     * @throws Exception
     */
    private function getIrKey(Log $log, int $protocol, int $address, int $command): array
    {
        $subId = (int) ($protocol . $address . $command);
        $module = $log->getModule();
        $valueModels = $this->valueRepository->getByTypeId(
            $module === null ? 0 : $module->getTypeId(),
            $subId,
            [],
            EthbridgeConstant::ATTRIBUTE_TYPE_IR_KEY
        );
        $data = [];

        foreach ($valueModels as $valueModel) {
            $data[$valueModel->getAttribute()->getKey()] = $valueModel->getValue();
        }

        return $data;
    }
}
