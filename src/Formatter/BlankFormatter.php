<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Module\Hc\Model\Log;

class BlankFormatter extends AbstractHcFormatter
{
    public function render(Log $log): ?string
    {
        $return = '<table><tr><th>Byte</th><th>Hex</th><th>Bin</th><th>Int</th></tr>';
        $byte = 1;

        for ($i = 0; $i < strlen($log->getRawData()); ++$i) {
            $data = substr($log->getRawData(), $i, 1);
            $return .= '<tr>';
            $return .= '<td>' . $byte . '</td>';
            $return .= '<td>' . $this->transformService->asciiToHex($data) . '</td>';
            $return .= '<td>' . $this->transformService->asciiToBin($data) . '</td>';
            $return .= '<td>' . $this->transformService->asciiToUnsignedInt($data) . '</td>';
            $return .= '</tr>';
            ++$byte;
        }

        $return .= '</table>';

        return $return;
    }
}
