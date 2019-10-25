<?php
namespace GibsonOS\Module\Hc\Utility\Formatter;

use GibsonOS\Module\Hc\Utility\Transform;

class Blank extends AbstractHcFormatter
{
    /**
     * @return string|null
     */
    public function render(): ?string
    {
        $return = '<table><tr><th>Byte</th><th>Hex</th><th>Bin</th><th>Int</th></tr>';
        $byte = 1;

        for ($i = 0; $i < strlen($this->data); $i+=2) {
            $data = substr($this->data, $i, 2);
            $return .= '<tr>';
            $return .= '<td>' . $byte . '</td>';
            $return .= '<td>' . $data . '</td>';
            $return .= '<td>' . Transform::hexToBin($data) . '</td>';
            $return .= '<td>' . Transform::hexToInt($data) . '</td>';
            $return .= '</tr>';
            $byte++;
        }

        $return .= '</table>';

        return $return;
    }
}