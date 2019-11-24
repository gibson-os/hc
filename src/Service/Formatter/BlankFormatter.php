<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Formatter;

use GibsonOS\Module\Hc\Model\Log;

class BlankFormatter extends AbstractHcFormatter
{
    public function render(Log $log): ?string
    {
        $return = '<table><tr><th>Byte</th><th>Hex</th><th>Bin</th><th>Int</th></tr>';
        $byte = 1;

        for ($i = 0; $i < strlen($log->getData()); $i += 2) {
            $data = substr($log->getData(), $i, 2);
            $return .= '<tr>';
            $return .= '<td>' . $byte . '</td>';
            $return .= '<td>' . $data . '</td>';
            $return .= '<td>' . $this->transform->hexToBin($data) . '</td>';
            $return .= '<td>' . $this->transform->hexToInt($data) . '</td>';
            $return .= '</tr>';
            ++$byte;
        }

        $return .= '</table>';

        return $return;
    }
}
