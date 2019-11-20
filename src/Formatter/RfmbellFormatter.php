<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Module\Hc\Constant\Rfmbell as RfmbellConstant;
use GibsonOS\Module\Hc\Service\ServerService;
use GibsonOS\Module\Hc\Transform;

class RfmbellFormatter extends AbstractFormatter
{
    /**
     * @return string|null
     */
    public function text(): ?string
    {
        if ($this->isDefaultType()) {
            return parent::text();
        }

        $returnList = [];
        $length = mb_strlen($this->data) / 2;

        for ($i = 0; $i < $length; ++$i) {
            $value = Transform::hexToInt($this->data, $i);

            if ($this->direction == ServerService::DIRECTION_INPUT) {
                // Eingang
                switch ($i) {
                    case RfmbellConstant::BUTTON1:
                        if ($value > 0) {
                            $returnList[] = 'Taster 1: <font color="#FF0000">gedrückt</font>';
                        } else {
                            $returnList[] = 'Taster 1: losgelassen';
                        }

                        break;
                    case RfmbellConstant::BUTTON2:
                        if ($value > 0) {
                            $returnList[] = 'Taster 2: <font color="#FF0000">gedrückt</font>';
                        } else {
                            $returnList[] = 'Taster 2: losgelassen';
                        }

                        break;
                    case RfmbellConstant::BELL:
                        if ($value > 0) {
                            $returnList[] = 'Wohnungsklingel: <font color="#FF0000">gedrückt</font>';
                        } else {
                            $returnList[] = 'Wohnungsklingel: losgelassen';
                        }

                        break;
                    case RfmbellConstant::SENSOR:
                        if ($value > 0) {
                            $returnList[] = 'Wohnungstür: geschloßen';
                        } else {
                            $returnList[] = 'Wohnungstür: <font color="#FF0000">offen</font>';
                        }

                        break;
                    case RfmbellConstant::DOOR_OPENER:
                        if ($value > 0) {
                            $returnList[] = 'Haus Türöffner: <font color="#FF0000">gedrückt</font>';
                        } else {
                            $returnList[] = 'Haus Türöffner: losgelassen';
                        }

                        break;
                    case RfmbellConstant::SILENT:
                        if ($value > 0) {
                            $returnList[] = 'Gong: <font color="#FF0000">deaktiviert</font>';
                        } else {
                            $returnList[] = 'Gong: aktiviert';
                        }

                        break;
                    case RfmbellConstant::BELL_HOUSE:
                        if ($value > 0) {
                            $returnList[] = 'Hausklingel: <font color="#FF0000">gedrückt</font>';
                        } else {
                            $returnList[] = 'Hausklingel: losgelassen';
                        }

                        break;
                    case RfmbellConstant::BELL_TIMER:
                        $returnList[] = 'Wohungsklingel Verzögerung: ' . $value . ' (' . number_format($value / RfmbellConstant::STEPS_PER_SECOND, 3) . 's)';

                        break;
                    case RfmbellConstant::DOOR_OPENER_TIMER:
                        $returnList[] = 'Türöffner Zeit: ' . $value . ' (' . number_format($value / RfmbellConstant::STEPS_PER_SECOND, 3) . 's)';

                        break;
                }
            } else {
                // Ausgang
                switch ($i) {
                    case RfmbellConstant::DOOR_OPENER:
                        if ($value > 0) {
                            $returnList[] = 'Haus Türöffner: <span style="color: #F00;">ausführen</span>';
                        } else {
                            $returnList[] = 'Haus Türöffner: beenden';
                        }

                        break;
                    case RfmbellConstant::SILENT:
                        if ($value > 0) {
                            $returnList[] = 'Gong: deaktivieren';
                        } else {
                            $returnList[] = 'Gong: aktivieren';
                        }

                        break;
                    case RfmbellConstant::BELL_TIMER:
                        $returnList[] = 'Wohnungsklingel Verzögerung: ' . $value . ' (' . number_format($value / RfmbellConstant::STEPS_PER_SECOND, 3) . 's)';

                        break;
                    case RfmbellConstant::DOOR_OPENER_TIMER:
                        $returnList[] = 'Türöffner Zeit: ' . $value . ' (' . number_format($value / RfmbellConstant::STEPS_PER_SECOND, 3) . 's)';

                        break;
                }
            }
        }

        return implode('<br />', $returnList);
    }
}
