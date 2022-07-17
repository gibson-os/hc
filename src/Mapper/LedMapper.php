<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper;

use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation\Led as AnimationLed;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Service\TransformService;

class LedMapper
{
    public const MAX_PROTOCOL_LEDS = 16384;

    public const RANGE_ADDRESS = 65535;

    private const MIN_RANGE_LEDS = 3;

    private const MIN_GROUP_LEDS = 2;

    public function __construct(private readonly TransformService $transformService)
    {
    }

    /**
     * @param Led[]|AnimationLed[] $leds
     *
     * @return string[]
     */
    public function mapToStrings(array $leds, int $maxLength): array
    {
        $ledsByNumber = [];

        foreach ($leds as $led) {
            $ledsByNumber[$led instanceof AnimationLed ? $led->getLed()->getNumber() : $led->getNumber()] = $led;
        }

        ksort($ledsByNumber);
        $colors = $this->getColorsByLeds($ledsByNumber);
        $data = [];

        foreach ($colors as $color) {
            sort($color['numbers']);
            $data = array_merge(
                $data,
                $this->getRangedColorStrings($ledsByNumber, $color),
                [$this->getSingleColorString($color)],
                $this->getGroupedColorStrings($color, $maxLength)
            );
        }

        return $data;
    }

    /**
     * @return Led[]
     */
    public function mapFromString(Module $module, string $data): array
    {
        $leds = [];

        for ($i = 0; $i < strlen($data);) {
            $address = $this->transformService->asciiToUnsignedInt(substr($data, $i, 2));
            $i += 2;

            if ($address === self::RANGE_ADDRESS) {
                $startAddress = $this->transformService->asciiToUnsignedInt(substr($data, $i, 2));
                $i += 2;
                $endAddress = $this->transformService->asciiToUnsignedInt(substr($data, $i, 2));
                $i += 2;
                $led = $this->getLedByString($module, $data, $i);

                for ($j = $startAddress; $j <= $endAddress; ++$j) {
                    $leds[$j] = $led;
                }

                continue;
            }

            if ($address > self::MAX_PROTOCOL_LEDS) {
                $groupAddresses = [];

                for ($j = 0; $j < $address - self::MAX_PROTOCOL_LEDS; ++$j) {
                    $groupAddresses[] = $this->transformService->asciiToUnsignedInt(substr($data, $i, 2));
                    $i += 2;
                }

                $led = $this->getLedByString($module, $data, $i);

                foreach ($groupAddresses as $groupAddress) {
                    $leds[$groupAddress] = $led;
                }

                continue;
            }

            $leds[$address] = $this->getLedByString($module, $data, $i);
        }

        return $leds;
    }

    private function getLedByString(Module $module, string $data, int &$i): Led
    {
        return (new Led())
            ->setModule($module)
            ->setRed($this->transformService->asciiToUnsignedInt($data, $i++))
            ->setGreen($this->transformService->asciiToUnsignedInt($data, $i++))
            ->setBlue($this->transformService->asciiToUnsignedInt($data, $i++))
            ->setFadeIn($this->transformService->asciiToUnsignedInt($data, $i) >> 4)
            ->setBlink($this->transformService->asciiToUnsignedInt($data, $i++) & 15)
        ;
    }

    /**
     * @template T of Led|AnimationLed
     *
     * @param T[]                           $leds
     * @param array{led: T, numbers: int[]} $color
     */
    private function getRangedColorStrings(array &$leds, array &$color): array
    {
        if (count($color['numbers']) < self::MIN_RANGE_LEDS) {
            return [];
        }

        $firstLed = reset($color['numbers']);
        $lastLed = null;
        $rangedLeds = [];
        $recursiveData = [];
        $data = chr(self::RANGE_ADDRESS >> 8) . chr(self::RANGE_ADDRESS & 255) .
            chr($firstLed >> 8) . chr($firstLed & 255);

        for ($i = $firstLed; $i <= end($color['numbers']); ++$i) {
            if (!isset($leds[$i])) {
                $recursiveData = $this->getRangedColorStrings($leds, $color);

                break;
            }

            $lastLed = $i;
            $colorLedIndex = array_search($i, $color['numbers']);

            if ($colorLedIndex === false) {
                continue;
            }

            unset($color['numbers'][$colorLedIndex], $leds[$i]);

            $rangedLeds[] = $i;
        }

        if (count($rangedLeds) < self::MIN_RANGE_LEDS) {
            $color['numbers'] = array_merge($color['numbers'], $rangedLeds);
            sort($color['numbers']);

            return $recursiveData;
        }

        $recursiveData[] =
            $data .
            chr($lastLed >> 8) . chr($lastLed & 255) .
            chr($color['led']->getRed()) .
            chr($color['led']->getGreen()) .
            chr($color['led']->getBlue()) .
            chr(($color['led']->getFadeIn() << 4) | $color['led']->getBlink())
        ;

        return $recursiveData;
    }

    /**
     * @param array{led: Led|AnimationLed, numbers: int[]} $color
     */
    private function getSingleColorString(array $color): string
    {
        if (count($color['numbers']) !== 1) {
            return '';
        }

        return
            chr($color['numbers'][0] >> 8) .
            chr($color['numbers'][0] & 255) .
            chr($color['led']->getRed()) .
            chr($color['led']->getGreen()) .
            chr($color['led']->getBlue()) .
            chr(($color['led']->getFadeIn() << 4) | $color['led']->getBlink());
    }

    /**
     * @param array{led: Led|AnimationLed, numbers: int[]} $color
     *
     * @return string[]
     */
    private function getGroupedColorStrings(array $color, int $maxLength): array
    {
        if (count($color['numbers']) < self::MIN_GROUP_LEDS) {
            return [];
        }

        $data = [];
        $dataString = '';
        $length = 6;
        $count = 0;

        foreach ($color['numbers'] as $led) {
            $length += 2;

            if ($length + 10 > $maxLength) {
                $data[] = $this->completeGroupedColorString($dataString, $count, $color['led']);
                $length = 0;
                $count = 0;
                $dataString = '';
            }

            $dataString .= chr($led >> 8) . chr($led & 255);
            ++$count;
        }

        $data[] = $this->completeGroupedColorString($dataString, $count, $color['led']);

        return $data;
    }

    private function completeGroupedColorString(string $data, int $count, Led|AnimationLed $led): string
    {
        $count += self::MAX_PROTOCOL_LEDS;

        return chr($count >> 8) . chr($count & 255) .
            $data .
            chr($led->getRed()) .
            chr($led->getGreen()) .
            chr($led->getBlue()) .
            chr(($led->getFadeIn() << 4) | $led->getBlink());
    }

    /**
     * @template T of Led|AnimationLed
     *
     * @param T[] $leds
     *
     * @return array<string, array{led: T, numbers: int[]}>
     */
    private function getColorsByLeds(array $leds): array
    {
        $colors = [];

        foreach ($leds as $led) {
            $key =
                $led->getRed() . '.' .
                $led->getGreen() . '.' .
                $led->getBlue() . '.' .
                $led->getFadeIn() . '.' .
                $led->getBlink();

            if (!isset($colors[$key])) {
                $colors[$key] = [
                    'led' => $led,
                    'numbers' => [],
                ];
            }

            $colors[$key]['numbers'][] = $led->getNumber();
        }

        return $colors;
    }
}
