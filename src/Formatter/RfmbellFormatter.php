<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Module\Hc\Constant\Rfmbell as RfmbellConstant;
use GibsonOS\Module\Hc\Dto\Direction;
use GibsonOS\Module\Hc\Model\Log;

class RfmbellFormatter extends AbstractFormatter
{
    public function text(Log $log): ?string
    {
        if ($this->isDefaultType($log)) {
            return parent::text($log);
        }

        $returnList = [];
        $length = mb_strlen($log->getData()) / 2;

        for ($i = 0; $i < $length; ++$i) {
            $value = $this->transformService->hexToInt($log->getData(), $i);

            if ($log->getDirection() === Direction::INPUT) {
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
