<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Attribute\Neopixel;

use Exception;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value as ValueModel;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository as ValueRepository;
use GibsonOS\Module\Hc\Repository\AttributeRepository as AttributeRepository;
use OutOfRangeException;

class LedService
{
    public const ATTRIBUTE_TYPE = 'led';

    public const ATTRIBUTE_KEY_CHANNEL = 'channel';

    public const ATTRIBUTE_KEY_RED = 'red';

    public const ATTRIBUTE_KEY_GREEN = 'green';

    public const ATTRIBUTE_KEY_BLUE = 'blue';

    public const ATTRIBUTE_KEY_FADE_IN = 'fadeIn';

    public const ATTRIBUTE_KEY_BLINK = 'blink';

    public const ATTRIBUTE_KEY_TOP = 'top';

    public const ATTRIBUTE_KEY_LEFT = 'left';

    private const ATTRIBUTES = [
        self::ATTRIBUTE_KEY_CHANNEL,
        self::ATTRIBUTE_KEY_RED,
        self::ATTRIBUTE_KEY_GREEN,
        self::ATTRIBUTE_KEY_BLUE,
        self::ATTRIBUTE_KEY_FADE_IN,
        self::ATTRIBUTE_KEY_BLINK,
        self::ATTRIBUTE_KEY_TOP,
        self::ATTRIBUTE_KEY_LEFT,
    ];

    private const IGNORE_ATTRIBUTES = [
        self::ATTRIBUTE_KEY_TOP,
        self::ATTRIBUTE_KEY_LEFT,
    ];

    /**
     * @var ValueRepository
     */
    private $valueRepository;

    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var Attribute[][]
     */
    private $ledsAttributes = [];

    public function __construct(
        AttributeRepository $attributeRepository,
        ValueRepository $valueRepository
    ) {
        $this->valueRepository = $valueRepository;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @throws DateTimeError
     * @throws SaveError
     * @throws SelectError
     */
    public function saveLeds(Module $slave, array $leds): void
    {
        $this->attributeRepository->startTransaction();

        try {
            foreach ($leds as $id => $led) {
                $this->saveLed($slave, $id, $led);
            }
        } catch (SaveError | SelectError $exception) {
            $this->attributeRepository->rollback();

            throw $exception;
        }

        $this->attributeRepository->commit();
    }

    /**
     * @return int[]
     */
    public function getLastIds(Module $slave, array $leds): array
    {
        $lastIds = [];

        foreach ($leds as $id => $led) {
            $lastIds[$this->getChannelById($slave, $id)] = $id;
        }

        return $lastIds;
    }

    public function getNumberById(Module $slave, int $id): int
    {
        $config = JsonUtility::decode((string) $slave->getConfig());
        $channelEndId = 0;

        foreach ($config['counts'] as $channel => $count) {
            if ($id < $channelEndId + $count) {
                return (int) ($id - $channelEndId);
            }

            $channelEndId += $count;
        }

        throw new OutOfRangeException('LED ' . $id . ' liegt in keinem Channel');
    }

    /**
     * @throws Exception
     */
    public function getChangedLeds(Module $slave, array $leds): array
    {
        $changedLeds = [];

        foreach ($leds as $id => $led) {
            $valueModels = $this->valueRepository->getByTypeId(
                $slave->getTypeId(),
                $id,
                [(int) $slave->getId()],
                self::ATTRIBUTE_TYPE
            );

            $changedLed = [];

            foreach ($valueModels as $valueModel) {
                $key = $valueModel->getAttribute()->getKey();

                if (!isset($led[$key])) {
                    continue;
                }

                if ($valueModel->getValue() == $led[$key]) {
                    continue;
                }

                $changedLed[$key] = $led[$key];
            }

            if (!empty($changedLed)) {
                $changedLeds[$id] = $changedLed;
            }
        }

        return $changedLeds;
    }

    public function getChangedSlaveLeds(array $changedLeds): array
    {
        $slaveLedsChanges = [];

        foreach ($changedLeds as $id => $changedLed) {
            $slaveLedChanges = [];

            foreach ($changedLed as $key => $attribute) {
                if (in_array($key, self::IGNORE_ATTRIBUTES)) {
                    continue;
                }

                $slaveLedChanges[$key] = $attribute;
            }

            if (!empty($slaveLedChanges)) {
                $slaveLedsChanges[$id] = $slaveLedChanges;
            }
        }

        return $slaveLedsChanges;
    }

    /**
     * @throws DateTimeError
     * @throws SaveError
     * @throws SelectError
     */
    private function saveLed(Module $slave, int $id, array $led): void
    {
        foreach ($led as $attribute => $value) {
            if (!in_array($attribute, self::ATTRIBUTES)) {
                continue;
            }

            (new ValueModel())
                ->setAttribute($this->getLedAttribute($slave, $id, $attribute))
                ->setOrder(0)
                ->setValue((string) $value)
                ->save()
            ;
        }
    }

    /**
     * @throws DateTimeError
     * @throws SaveError
     * @throws SelectError
     */
    private function getLedAttribute(Module $slave, int $id, string $key): Attribute
    {
        if (!isset($this->ledsAttributes[$id])) {
            $this->ledsAttributes[$id] = [];
        } elseif (!isset($this->ledsAttributes[$id][$key])) {
            $this->addAttribute($slave, $id, $key);
        }

        if (isset($this->ledsAttributes[$id][$key])) {
            return $this->ledsAttributes[$id][$key];
        }

        foreach ($this->attributeRepository->getByModule($slave, $id, null, self::ATTRIBUTE_TYPE) as $attribute) {
            $this->ledsAttributes[$id][$attribute->getKey()] = $attribute;
        }

        if (!isset($this->ledsAttributes[$id][$key])) {
            $this->addAttribute($slave, $id, $key);
        }

        return $this->ledsAttributes[$id][$key];
    }

    /**
     * @throws DateTimeError
     * @throws SaveError
     */
    private function addAttribute(Module $slave, int $id, string $key): void
    {
        $this->ledsAttributes[$id][$key] = (new Attribute())
            ->setTypeId($slave->getTypeId())
            ->setModuleId($slave->getId())
            ->setSubId($id)
            ->setKey($key)
            ->setType(self::ATTRIBUTE_TYPE)
        ;
        $this->ledsAttributes[$id][$key]->save();
    }

    /**
     * @throws DeleteError
     */
    public function deleteUnusedLeds(Module $slave, array $leds)
    {
        if (count($leds)) {
            ksort($leds);
            $leds = array_keys($leds);
            $id = end($leds);
        } else {
            $id = -1;
        }

        $this->attributeRepository->deleteWithBiggerSubIds(
            $slave,
            (int) $id,
            null,
            self::ATTRIBUTE_TYPE
        );
    }

    /**
     * @throws OutOfRangeException
     */
    public function getChannelCounts(Module $slave, array $leds): array
    {
        $counts = [];
        $config = JsonUtility::decode($slave->getConfig() ?? '[]');

        for ($i = 0; $i < $config['channels']; ++$i) {
            $counts[$i] = 0;
        }

        foreach ($leds as $led) {
            ++$counts[$led[self::ATTRIBUTE_KEY_CHANNEL]];
        }

        return $counts;
    }

    /**
     * @throws Exception
     *
     * @return ValueModel[]
     */
    public function getById(Module $slave, int $id, string $key = null): array
    {
        return $this->valueRepository->getByTypeId(
            $slave->getTypeId(),
            $id,
            [(int) $slave->getId()],
            self::ATTRIBUTE_TYPE,
            $key
        );
    }

    private function getChannelById(Module $slave, int $id): int
    {
        $config = JsonUtility::decode((string) $slave->getConfig());
        $channelEndId = 0;

        foreach ($config['counts'] as $channel => $count) {
            $channelEndId += $count;

            if ($id < $channelEndId) {
                return $channel;
            }
        }

        throw new OutOfRangeException('LED ' . $id . ' liegt in keinem Channel');
    }
}
