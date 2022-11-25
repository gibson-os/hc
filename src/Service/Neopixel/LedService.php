<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Neopixel;

use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation\Led as AnimationLed;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Repository\Neopixel\LedRepository;
use GibsonOS\Module\Hc\Service\Module\NeopixelService;

class LedService
{
    public function __construct(private readonly LedRepository $ledRepository)
    {
    }

    /**
     * @param Led[] $leds
     *
     * @throws \JsonException
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

    /**
     * @throws \JsonException
     */
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

        throw new \OutOfRangeException('LED ' . $id . ' liegt in keinem Channel');
    }

    /**
     * @throws \Exception
     *
     * @return Led[]
     */
    public function getActualState(Module $module): array
    {
        $actualLeds = [];
        $config = JsonUtility::decode($module->getConfig() ?? '[]');
        $leds = $this->ledRepository->getByModule($module);
        $ledConfigCount = array_sum($config[NeopixelService::CONFIG_COUNTS]);
        $ledCount = count($leds);

        if ($ledCount === $ledConfigCount) {
            return $leds;
        }

        for ($i = $ledCount; $i < $ledConfigCount; ++$i) {
            $actualLeds[$i] = (new Led())
                ->setModule($module)
                ->setNumber($i)
            ;
        }

        return $actualLeds;
    }

    /**
     * @template T of Led|AnimationLed
     *
     * @param T[] $oldLeds
     * @param T[] $newLeds
     *
     * @return T[]
     */
    public function getChanges(array $oldLeds, array $newLeds): array
    {
        return array_udiff_assoc($newLeds, $oldLeds, static function (Led|AnimationLed $newLed, Led|AnimationLed $oldLed) {
            return count(array_diff_assoc($newLed->jsonSerialize(), $oldLed->jsonSerialize()));
        });
    }

    /**
     * @param Led[] $leds
     *
     * @throws \OutOfRangeException
     * @throws \JsonException
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
     * @throws \JsonException
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

        throw new \OutOfRangeException('LED ' . $led->getNumber() . ' liegt in keinem Channel');
    }
}
