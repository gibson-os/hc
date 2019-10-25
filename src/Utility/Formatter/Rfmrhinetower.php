<?php
namespace GibsonOS\Module\Hc\Utility\Formatter;

use GibsonOS\Core\Utility\ArrayKey;
use GibsonOS\Module\Hc\Constant\Rfmrhinetower as RfmrhinetowerConstant;
use GibsonOS\Module\Hc\Service\Master;
use GibsonOS\Module\Hc\Service\Server;
use GibsonOS\Module\Hc\Utility\Transform;

class Rfmrhinetower extends AbstractFormatter
{
    /** @var array Große LED Liste */
    private $_keyList = [
        0 => [0, 1, 2, 3, 4, 5, 6, 7],
        1 => [0, 1, 2, 3, 4, 5, 6, 7],
        2 => [0, 1, 2, 3, 4, 5, 6, 7],
        3 => [0, 1, 2, 3, 4, 5, 6, 7],
        4 => [0, 1, 2, 3, 4, 5, 6, 7],
        5 => [0, 1, 2, 3, 4, 5, 6, 7],
        6 => [0, 1, 2, 3, 4, 5, 6, 7],
        7 => [0, 1, 2, 3, 4, 5, 6, 7]
    ];
    /** @var array Kleine LED Liste */
    private $_smallKeyList = [
        1 => [0, 1],
        3 => [0, 1],
        5 => [0, 1, 2, 3, 4],
        6 => [0, 1, 2, 3, 4, 5, 6, 7],
        7 => [0, 1, 2, 3, 4, 5, 6, 7]
    ];

    /**
     * @return null|string
     */
    public function text(): ?string
    {
        $data = $this->data;

        switch ($this->type) {
            case Master::TYPE_STATUS:
                if ($this->direction === Server::DIRECTION_INPUT) {
                    return $this->clockLogFormat($data);
                }

                break;
            case Master::TYPE_DATA:
                switch (mb_substr($data, 0, 2)) {
                    case RfmrhinetowerConstant::MODE_SET_CLOCK:
                        return $this->clockLogFormat(mb_substr($data, 2));
                    case RfmrhinetowerConstant::MODE_SHOW_CLOCK:
                        if (Transform::hexToInt($data, 1)) {
                            return 'Uhrzeit anzeigen';
                        } else {
                            return 'Uhrzeit nicht anzeigen';
                        }
                    case RfmrhinetowerConstant::MODE_PLAY_ANIMATION:
                        return 'Starte Animation ' . Transform::hexToInt($data, 1);
                }
        }

        return parent::text();
    }

    /**
     * @return null|string
     */
    public function render(): ?string
    {
        $data = $this->data;

        switch ($this->type) {
            case Master::TYPE_STATUS:
                if ($this->direction === Server::DIRECTION_INPUT) {
                    return $this->ledLogFormat(mb_substr($data, 16));
                }

                break;
            case Master::TYPE_DATA:
                if (mb_substr($data, 0, 2) == RfmrhinetowerConstant::MODE_SET_LED) {
                    return $this->ledLogFormat(mb_substr($data, 2));
                }

                break;
        }

        return parent::render();
    }

    /**
     * Gibt LED Liste zurück
     *
     * Gibt LED Liste von einem Datenstring zurück.
     *
     * @param string $data Daten
     * @param bool $smallList Kleine LED Liste
     * @param string $prefix Index Präfix
     * @return array
     */
    public function getArrayFromDataString($data, $smallList = false, $prefix = 'l')
    {
        $ledList = [];
        $keyList = $this->_keyList;

        if (mb_strlen($data) <= 52){
            $keyList = $this->_smallKeyList;
        }

        $i = 0;

        foreach ($keyList as $x => $list) {
            if (
                $smallList &&
                !array_key_exists($x, $this->_smallKeyList)
            ) {
                $i += 16;
                continue;
            }

            if (!ArrayKey::exists($prefix . $x, $ledList)) {
                $ledList[$prefix . $x] = [];
            }

            foreach ($keyList[$x] as $y) {
                if (
                    $smallList &&
                    !in_array($y, $this->_smallKeyList[$x])
                ) {
                    $i += 2;
                    continue;
                }

                $ledList[$prefix . $x][$prefix . $y] = [
                    'brightness' => Transform::hexToInt(mb_substr($data, $i++, 1)),
                    'blink' => Transform::hexToInt(mb_substr($data, $i++, 1))
                ];
            }
        }

        return $ledList;
    }

    /**
     * Gibt Datenstring zurück
     *
     * Gibt Datenstring von einer LED Liste zurück.
     *
     * @param array $ledList LED Liste
     * @param bool $withClock Uhrzeit weiterhin anzeigen
     * @return string
     */
    public function getDataStringFromArray($ledList, $withClock = false)
    {
        $data = RfmrhinetowerConstant::MODE_SET_LED;

        foreach ($this->addMissingDataElements($ledList, false, !$withClock) as $x => $pc) {
            foreach ($pc as $y => $led) {
                $data .= sprintf("%02x", ($led['brightness']<<4) + $led['blink']);
            }
        }

        return $data;
    }

    /**
     * Fügt fehlende Elemente hinzu
     *
     * Fügt fehlende Elemente einer LED Liste hinzu.
     *
     * @param array $ledList LED Liste
     * @param bool $fullList Volle Liste mit Uhrzeit LEDs
     * @param bool $smallList Kleine LED Liste
     * @return array
     */
    private function addMissingDataElements($ledList, $fullList = false, $smallList = false)
    {
        $keyList = $this->_keyList;
        $newLedList = [];

        if (
            $smallList ||
            !$fullList &&
            !(
                ArrayKey::exists(0, $ledList) ||
                (ArrayKey::exists(1, $ledList) && ArrayKey::exists([2, 3, 4, 5, 6, 7], $ledList[1])) ||
                ArrayKey::exists(2, $ledList) ||
                (ArrayKey::exists(3, $ledList) && ArrayKey::exists([2, 3, 4, 5, 6, 7], $ledList[3])) ||
                ArrayKey::exists(4, $ledList) ||
                (ArrayKey::exists(5, $ledList) && ArrayKey::exists([5, 6, 7], $ledList[5]))
            )
        ) {
            $keyList = $this->_smallKeyList;
        }

        foreach ($keyList as $x => $list) {
            if (!ArrayKey::exists($x, $newLedList)) {
                $newLedList[$x] = [];
            }

            foreach ($list as $y) {
                $newLedList[$x][$y] = [
                    'brightness' => 0,
                    'blink' => 0
                ];

                if (
                    ArrayKey::exists($x, $ledList) &&
                    ArrayKey::exists($y, $ledList[$x])
                ) {
                    $newLedList[$x][$y] = $ledList[$x][$y];
                }
            }

            ksort($newLedList[$x]);
        }

        ksort($newLedList);
        return $newLedList;
    }

    /**
     * Formatiert Uhrzeit
     *
     * Gibt Uhrzeit formatiert zurück.
     *
     * @param string $data Daten
     * @return string
     */
    private function clockLogFormat($data)
    {
        $year = (Transform::hexToInt($data, 0)<<8) | Transform::hexToInt($data, 1);
        $month = Transform::hexToInt($data, 2);
        $day = Transform::hexToInt($data, 3);
        $hour = Transform::hexToInt($data, 4);
        $minute = Transform::hexToInt($data, 5);
        $second = Transform::hexToInt($data, 6);

        return sprintf("%04u-%02u-%02u", $year, $month+1, $day+1) . ' '
            . sprintf("%02u:%02u:%02u", $hour, $minute, $second);
    }

    /**
     * Formatiert LEDs
     *
     * Gibt LEDs formatiert zurück.
     *
     * @param string $data Daten
     * @return string
     */
    private function ledLogFormat($data)
    {
        $return = null;
        $ledList = $this->getArrayFromDataString($data, false, null);
        $matches = [
            0 => [4 => [5, 0, 'F00']],
            2 => [
                0 => [6, 2, '00F'],
                1 => [6, 3, '00F'],
                2 => [6, 4, '00F'],
                3 => [6, 5, '00F'],
                5 => [6, 6, '00F'],
                6 => [6, 7, '00F'],
                7 => [6, 0, '00F'],
                8 => [6, 1, '00F']
            ],
            4 => [
                0 => [7, 2, 'FFF'],
                1 => [7, 3, 'FFF'],
                2 => [7, 4, 'FFF'],
                3 => [7, 5, 'FFF'],
                5 => [7, 6, 'FFF'],
                6 => [7, 7, 'FFF'],
                7 => [7, 0, 'FFF'],
                8 => [7, 1, 'FFF']
            ],
            6 => [4 => [5, 5, 'FF0']],
            7 => [4 => [5, 6, 'FF0']],
            9 => [4 => [5, 7, 'FF0']],
            10 => [4 => [4, 0, 'FF0']],
            11 => [4 => [4, 1, 'FF0']],
            12 => [4 => [4, 2, 'FF0']],
            13 => [4 => [4, 3, 'FF0']],
            14 => [4 => [4, 4, 'FF0']],
            15 => [4 => [4, 5, 'FF0']],
            16 => [4 => [4, 6, 'FF0']],
            17 => [4 => [4, 7, 'FF0']],
            19 => [
                1 => [5, 1, 'F00'],
                3 => [5, 4, 'F00'],
                5 => [5, 3, 'F00'],
                7 => [5, 2, 'F00']
            ],
            21 => [4 => [3, 2, 'FF0']],
            22 => [4 => [3, 3, 'FF0']],
            23 => [4 => [3, 4, 'FF0']],
            24 => [4 => [3, 5, 'FF0']],
            25 => [4 => [3, 6, 'FF0']],
            27 => [4 => [3, 7, 'FF0']],
            28 => [4 => [2, 0, 'FF0']],
            29 => [4 => [2, 1, 'FF0']],
            30 => [4 => [2, 2, 'FF0']],
            31 => [4 => [2, 3, 'FF0']],
            32 => [4 => [2, 4, 'FF0']],
            33 => [4 => [2, 5, 'FF0']],
            34 => [4 => [2, 6, 'FF0']],
            35 => [4 => [2, 7, 'FF0']],
            37 => [
                1 => [3, 1, 'F00'],
                3 => [1, 1, 'F00'],
                5 => [1, 0, 'F00'],
                7 => [3, 0, 'F00']
            ],
            39 => [4 => [1, 2, 'FF0']],
            40 => [4 => [1, 3, 'FF0']],
            41 => [4 => [1, 4, 'FF0']],
            42 => [4 => [1, 5, 'FF0']],
            43 => [4 => [1, 6, 'FF0']],
            45 => [4 => [1, 7, 'FF0']],
            46 => [4 => [0, 0, 'FF0']],
            47 => [4 => [0, 1, 'FF0']],
            48 => [4 => [0, 2, 'FF0']],
            49 => [4 => [0, 3, 'FF0']],
            50 => [4 => [0, 4, 'FF0']],
            51 => [4 => [0, 5, 'FF0']],
            52 => [4 => [0, 6, 'FF0']],
            53 => [4 => [0, 7, 'FF0']]
        ];

        for ($x = 0; $x < 54; $x++) {
            for ($y = 0; $y < 9; $y++) {
                if (
                    array_key_exists($x, $matches) &&
                    array_key_exists($y, $matches[$x])
                ) {
                    $match = $matches[$x][$y];
                    $color = '000';

                    if (
                        array_key_exists($match[0], $ledList) &&
                        array_key_exists($match[1], $ledList[$match[0]]) &&
                        $ledList[$match[0]][$match[1]]['brightness']
                    ) {
                        $color = $match[2];
                    }

                    $return .= '<div class="hc_led_thumb_small" style="border: 1px solid #000; background-color: #' . $color . ';" title="#' . $color . '"></div>';
                } else {
                    $return .= '<div class="hc_led_thumb_small" style="background: #FFF;"></div>';
                }
            }

            $return .= '<br style="clear: left;" />';
        }

        return $return;
    }
}