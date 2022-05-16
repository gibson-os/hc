<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Neopixel;

use Exception;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use JsonException;
use OutOfRangeException;

class LedService
{
    /**
     * @param Led[] $leds
     *
     * @throws JsonException
     *
     * @return int[]
     */
    public function getLastIds(Module $slave, array $leds): array
    {
        $lastIds = [];

        foreach ($leds as $led) {
            $lastIds[$this->setLedChannel($slave, $led)] = $led->getNumber();
        }

        return $lastIds;
    }

    public function getNumberById(Module $slave, int $id): int
    {
        $config = JsonUtility::decode((string) $slave->getConfig());
        $channelEndId = 0;

        foreach ($config['counts'] as $count) {
            if ($id < $channelEndId + $count) {
                return (int) ($id - $channelEndId);
            }

            $channelEndId += $count;
        }

        throw new OutOfRangeException('LED ' . $id . ' liegt in keinem Channel');
    }

    /**
     * @throws Exception
     *
     * @return Led[]
     */
    public function getActualState(Module $module): array
    {
        $actualLeds = [];
        $config = JsonUtility::decode($module->getConfig() ?? '[]');

        for ($i = 0; $i < array_sum($config[NeopixelService::CONFIG_COUNTS]); ++$i) {
            $led = new Led($module, $i);

            foreach ($this->getById($module, $i) as $attributeValue) {
                $led->{'set' . ucfirst($attributeValue->getAttribute()->getKey())}((int) $attributeValue->getValue());
            }

            $actualLeds[$i] = $led;
        }

        return $actualLeds;
    }

    /**
     * @param Led[] $oldLeds
     * @param Led[] $newLeds
     *
     * @return Led[]
     */
    public function getChanges(array $oldLeds, array $newLeds): array
    {
        return array_udiff_assoc($newLeds, $oldLeds, static function (Led $newLed, Led $oldLed) {
            $newLedOnlyColor = $newLed->isOnlyColor();
            $oldLedOnlyColor = $oldLed->isOnlyColor();
            $newLed->setOnlyColor(true);
            $oldLed->setOnlyColor(true);
            $count = count(array_diff_assoc($newLed->jsonSerialize(), $oldLed->jsonSerialize()));
            $newLed->setOnlyColor($newLedOnlyColor);
            $oldLed->setOnlyColor($oldLedOnlyColor);

            return $count;
        });
    }

    /**
     * @param Led[] $leds
     *
     * @throws OutOfRangeException
     * @throws JsonException
     */
    public function getChannelCounts(Module $slave, array $leds): array
    {
        $counts = [];
        $config = JsonUtility::decode($slave->getConfig() ?? '[]');

        for ($i = 0; $i < $config['channels']; ++$i) {
            $counts[$i] = 0;
        }

        foreach ($leds as $led) {
            ++$counts[$led->getChannel()];
        }

        return $counts;
    }

    /**
     * @throws JsonException
     */
    private function setLedChannel(Module $slave, Led $led): int
    {
        $config = JsonUtility::decode((string) $slave->getConfig());
        $channelEndId = 0;

        foreach ($config['counts'] as $channel => $count) {
            $channelEndId += $count;

            if ($led->getNumber() < $channelEndId) {
                $led->setChannel($channel);

                return $channel;
            }
        }

        throw new OutOfRangeException('LED ' . $led->getNumber() . ' liegt in keinem Channel');
    }
}
