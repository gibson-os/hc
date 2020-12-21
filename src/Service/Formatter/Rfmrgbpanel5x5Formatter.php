<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Formatter;

use GibsonOS\Module\Hc\Constant\Rfmrgbpanel5x5 as Rfmrgbpanel5x5Constant;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\MasterService;

class Rfmrgbpanel5x5Formatter extends AbstractFormatter
{
    public function text(Log $log): ?string
    {
        if ($this->isDefaultType($log)) {
            return parent::text($log);
        }

        $data = $log->getData();

        if ($log->getType() === MasterService::TYPE_STATUS) {
            if ($log->getDirection() === Log::DIRECTION_OUTPUT) {
                return parent::text($log);
            }

            $sequenceActive = $this->transform->hexToInt($data, 0);
            $sequenceId = $this->transform->hexToInt(mb_substr($data, 2));

            return 'Sequenz ' . $sequenceId . ($sequenceActive ? ' aktiv' : ' gestoppt');
        }

        if (mb_substr($data, 0, 2) == Rfmrgbpanel5x5Constant::SEQUENCE_BYTE) {
            $data = mb_substr($data, 2);

            switch (mb_substr($data, 0, 2)) {
                case Rfmrgbpanel5x5Constant::SEQUENCE_START_BYTE:
                    return 'Übertragung von Sequenz ' . $this->transform->hexToInt($data) . ' starten';
                case Rfmrgbpanel5x5Constant::SEQUENCE_RUN_BYTE:
                    switch (mb_substr($data, 2, 2)) {
                        case Rfmrgbpanel5x5Constant::SEQUENCE_PLAY_BYTE:
                            return 'Sequenz starten';
                        case Rfmrgbpanel5x5Constant::SEQUENCE_PAUSE_BYTE:
                            return 'Sequenz pausieren';
                        default:
                            return 'Sequenz stoppen';
                    }
                    // no break
                default:
                    return 'Sequenz Step ' . $this->transform->hexToInt($data, 0);
            }
        }

        return parent::text($log);
    }

    public function render(Log $log): ?string
    {
        if ($this->isDefaultType($log)) {
            return parent::render($log);
        }

        $data = $log->getData();

        if (mb_substr($data, 0, 2) == Rfmrgbpanel5x5Constant::SEQUENCE_BYTE) {
            $data = mb_substr($data, 2);

            switch (mb_substr($data, 0, 2)) {
                case Rfmrgbpanel5x5Constant::SEQUENCE_START_BYTE:
                case Rfmrgbpanel5x5Constant::SEQUENCE_RUN_BYTE:
                    return parent::render($log);
            }

            $data = mb_substr($data, 4);
        }

        return $this->renderLeds($data);
    }

    public function renderLeds(string $ledList): ?string
    {
        $return = null;
        $ledList = $this->getLedList($ledList);

        for ($i = 0; $i < 5; ++$i) {
            for ($j = $i + 20; $j >= 0; $j -= 5) {
                $key = 'l' . ($j + 1);

                if (
                    array_key_exists($key, $ledList) &&
                    array_key_exists('color', $ledList[$key]) &&
                    null !== $ledList[$key]['color'] &&
                    mb_strlen($ledList[$key]['color']) == 3
                ) {
                    $color = $ledList[$key]['color'];
                } else {
                    $color = '000';
                }

                $return .= '<div class="hc_led_thumb" style="background-color: #' . $color . ';" title="#' . $color . '"></div>';
            }

            $return .= '<br style="clear: left;" />';
        }

        return $return;
    }

    public function getLedList(string $data): array
    {
        $ledList = [];

        if (mb_substr($data, 0, 2) == Rfmrgbpanel5x5Constant::UNADDRESSED_BYTE) {
            // 2 Bytes pro LED 2=Effekt|Red 3=Green|Blue
            for ($i = 2; $i < mb_strlen($data); $i += 4) {
                $ledList['l' . ($i + 2) / 4] = [
                    'effect' => mb_substr($data, $i, 1),
                    'color' => mb_substr($data, $i + 1, 3),
                ];
            }
        } else {
            // 3 Bytes pro LED 1=Adresse; 2=Effekt|Red 3=Green|Blue
            for ($i = 2; $i < mb_strlen($data); $i += 6) {
                $address = $this->transform->hexToInt(mb_substr($data, $i, 2));

                if ($address > Rfmrgbpanel5x5Constant::LED_COUNT) { // compressed
                    $ledCount = $address - Rfmrgbpanel5x5Constant::LED_COUNT;
                    $effect = mb_substr($data, $i + 2 + ($ledCount * 2), 1);
                    $color = mb_substr($data, $i + 3 + ($ledCount * 2), 3);

                    for ($j = 0; $j < $ledCount; ++$j) {
                        $i += 2;
                        $ledList['l' . $this->transform->hexToInt(mb_substr($data, $i, 2))] = [
                            'effect' => $effect,
                            'color' => $color,
                        ];
                    }
                } else {
                    $ledList['l' . $address] = [
                        'effect' => mb_substr($data, $i + 2, 1),
                        'color' => mb_substr($data, $i + 3, 3),
                    ];
                }
            }
        }

        return $ledList;
    }
}
