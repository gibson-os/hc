<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService as LedAttribute;
use GibsonOS\Module\Hc\Transform;

class NeopixelFormatter extends AbstractHcFormatter
{
    private const MAX_PROTOCOL_LEDS = 16384;

    private const RANGE_ADDRESS = 65535;

    private const MIN_RANGE_LEDS = 3;

    private const MIN_GROUP_LEDS = 2;

    /**
     * @param array $leds
     * @param int   $maxLength
     *
     * @return string[]
     */
    public function getLedsAsStrings(array $leds, int $maxLength): array
    {
        ksort($leds);
        $colors = self::getColorsByLeds($leds);
        $data = [];

        foreach ($colors as $color) {
            sort($color['leds']);
            $data[] = self::getRangedColorString($leds, $color);
            $data[] = self::getSingleColorString($color);
            $data = array_merge($data, self::getGroupedColorStrings($color, $maxLength));
        }

        return $data;
    }

    /**
     * @param string $data
     *
     * @return array
     */
    public function getLedsAsArray(string $data): array
    {
        $leds = [];

        for ($i = 2; $i < strlen($data);) {
            $address = Transform::asciiToInt(substr($data, 0, 2));

            if ($address > self::MAX_PROTOCOL_LEDS) {
                for ($j = 0; $j < $address - self::MAX_PROTOCOL_LEDS; ++$j) {
                    $addressFromGroup = Transform::asciiToInt(substr($data, $i, 2));
                    $i += 2;
                    $leds[$addressFromGroup] = [
                        LedAttribute::ATTRIBUTE_KEY_RED => Transform::asciiToInt($data, $i++),
                        LedAttribute::ATTRIBUTE_KEY_GREEN => Transform::asciiToInt($data, $i++),
                        LedAttribute::ATTRIBUTE_KEY_BLUE => Transform::asciiToInt($data, $i++),
                        LedAttribute::ATTRIBUTE_KEY_FADE_IN => Transform::asciiToInt($data, $i++) >> 4,
                        LedAttribute::ATTRIBUTE_KEY_BLINK => Transform::asciiToInt($data, $i++) & 15,
                    ];
                }
            }

            $leds[$address] = [
                LedAttribute::ATTRIBUTE_KEY_RED => Transform::asciiToInt($data, $i++),
                LedAttribute::ATTRIBUTE_KEY_GREEN => Transform::asciiToInt($data, $i++),
                LedAttribute::ATTRIBUTE_KEY_BLUE => Transform::asciiToInt($data, $i++),
                LedAttribute::ATTRIBUTE_KEY_FADE_IN => Transform::asciiToInt($data, $i++) >> 4,
                LedAttribute::ATTRIBUTE_KEY_BLINK => Transform::asciiToInt($data, $i++) & 15,
            ];
        }

        return $leds;
    }

    /**
     * @param array $leds
     * @param array $color
     *
     * @return string
     */
    private function getRangedColorString(array &$leds, array &$color): string
    {
        if (count($color['leds']) < self::MIN_RANGE_LEDS) {
            return '';
        }

        $firstLed = reset($color['leds']);
        $lastLed = null;
        $rangedLeds = [];
        $recursiveData = '';
        $data = chr(self::RANGE_ADDRESS >> 8) . chr(self::RANGE_ADDRESS & 255) .
            chr($firstLed >> 8) . chr($firstLed & 255);

        for ($i = $firstLed; $i <= end($color['leds']); ++$i) {
            if (!isset($leds[$i])) {
                $recursiveData = self::getRangedColorString($leds, $color);

                break;
            }

            $lastLed = $i;
            $colorLedIndex = array_search($i, $color['leds']);

            if ($colorLedIndex === false) {
                continue;
            }

            unset($color['leds'][$colorLedIndex], $leds[$i]);

            $rangedLeds[] = $i;
        }

        if (count($rangedLeds) < self::MIN_RANGE_LEDS) {
            $color['leds'] = array_merge($color['leds'], $rangedLeds);
            sort($color['leds']);

            return $recursiveData;
        }

        $data .= chr($lastLed >> 8) . chr($lastLed & 255) .
            chr($color[LedAttribute::ATTRIBUTE_KEY_RED]) .
            chr($color[LedAttribute::ATTRIBUTE_KEY_GREEN]) .
            chr($color[LedAttribute::ATTRIBUTE_KEY_BLUE]) .
            chr(
                ($color[LedAttribute::ATTRIBUTE_KEY_FADE_IN] << 4) |
                $color[LedAttribute::ATTRIBUTE_KEY_BLINK]
            );

        return $data . $recursiveData;
    }

    /**
     * @param array $color
     *
     * @return string
     */
    private function getSingleColorString(array $color): string
    {
        if (count($color['leds']) !== 1) {
            return '';
        }

        return
            chr($color['leds'][0] >> 8) .
            chr($color['leds'][0] & 255) .
            chr($color[LedAttribute::ATTRIBUTE_KEY_RED]) .
            chr($color[LedAttribute::ATTRIBUTE_KEY_GREEN]) .
            chr($color[LedAttribute::ATTRIBUTE_KEY_BLUE]) .
            chr(
                ($color[LedAttribute::ATTRIBUTE_KEY_FADE_IN] << 4) |
                $color[LedAttribute::ATTRIBUTE_KEY_BLINK]
            );
    }

    /**
     * @param array $color
     * @param int   $maxLength
     *
     * @return string[]
     */
    private function getGroupedColorStrings(array $color, int $maxLength): array
    {
        if (count($color['leds']) < self::MIN_GROUP_LEDS) {
            return [];
        }

        $data = [];
        $dataString = '';
        $length = 6;
        $count = 0;
        // @todo maximale buffergröße beachten
        foreach ($color['leds'] as $led) {
            $length += 2;

            if ($length > $maxLength) {
                $data[] = self::completeGroupedColorString($dataString, $count, $color);
                $length = 0;
                $count = 0;
                $dataString = '';
            }

            $dataString .= chr($led >> 8) . chr($led & 255);
            ++$count;
        }

        $data[] = self::completeGroupedColorString($dataString, $count, $color);

        return $data;
    }

    private function completeGroupedColorString(string $data, int $count, array $color): string
    {
        $count += self::MAX_PROTOCOL_LEDS;

        return chr($count >> 8) . chr($count & 255) .
            $data .
            chr($color[LedAttribute::ATTRIBUTE_KEY_RED]) .
            chr($color[LedAttribute::ATTRIBUTE_KEY_GREEN]) .
            chr($color[LedAttribute::ATTRIBUTE_KEY_BLUE]) .
            chr(
                ($color[LedAttribute::ATTRIBUTE_KEY_FADE_IN] << 4) |
                $color[LedAttribute::ATTRIBUTE_KEY_BLINK]
            );
    }

    /**
     * @param array $leds
     *
     * @return array
     */
    private function getColorsByLeds(array $leds): array
    {
        $colors = [];

        foreach ($leds as $id => $led) {
            $key =
                $led[LedAttribute::ATTRIBUTE_KEY_RED] . '.' .
                $led[LedAttribute::ATTRIBUTE_KEY_GREEN] . '.' .
                $led[LedAttribute::ATTRIBUTE_KEY_BLUE] . '.' .
                $led[LedAttribute::ATTRIBUTE_KEY_FADE_IN] . '.' .
                $led[LedAttribute::ATTRIBUTE_KEY_BLINK];

            if (!isset($colors[$key])) {
                $colors[$key] = $led;
                $colors[$key]['leds'] = [];
            }

            $colors[$key]['leds'][] = $id;
        }

        return $colors;
    }
}
