<?php
namespace GibsonOS\Module\Hc\Service\Attribute\Neopixel;

use Exception;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Utility\Json;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value as ValueModel;
use GibsonOS\Module\Hc\Model\Module as ModuleModel;
use GibsonOS\Module\Hc\Repository\Attribute as AttributeRepository;
use GibsonOS\Module\Hc\Repository\Attribute\Value as ValueRepository;
use OutOfRangeException;

class Led
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
        self::ATTRIBUTE_KEY_LEFT
    ];
    private const IGNORE_ATTRIBUTES = [
        self::ATTRIBUTE_KEY_TOP,
        self::ATTRIBUTE_KEY_LEFT
    ];

    /**
     * @var ModuleModel
     */
    private $slave;
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
        ModuleModel $slave,
        AttributeRepository $attributeRepository,
        ValueRepository $valueRepository
    ) {
        $this->slave = $slave;
        $this->valueRepository = $valueRepository;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @param array $leds
     * @throws SaveError
     * @throws SelectError
     */
    public function saveLeds(array $leds): void
    {
        $this->attributeRepository->startTransaction();

        try {
            foreach ($leds as $id => $led) {
                $this->saveLed($id, $led);
            }
        } catch (SaveError | SelectError $exception) {
            $this->attributeRepository->rollback();
            throw $exception;
        }

        $this->attributeRepository->commit();
    }

    /**
     * @param array $leds
     * @return int[]
     * @throws OutOfRangeException
     */
    public function getLastIds(array $leds): array
    {
        $lastIds = [];

        foreach ($leds as $id => $led) {
            $lastIds[$this->getChannelById($id)] = $id;
        }

        return $lastIds;
    }

    /**
     * @param int $id
     * @return int
     * @throws OutOfRangeException
     */
    public function getNumberById(int $id): int
    {
        $config = Json::decode($this->slave->getConfig());
        $channelEndId = 0;

        foreach ($config['counts'] as $channel => $count) {
            if ($id < $channelEndId + $count) {
                return $id - $channelEndId;
            }

            $channelEndId += $count;
        }

        throw new OutOfRangeException('LED ' . $id . ' liegt in keinem Channel');
    }

    /**
     * @param array $leds
     * @return array
     */
    public function getChangedLeds(array $leds): array
    {
        $changedLeds = [];

        foreach ($leds as $id => $led) {
            $valueModels = $this->valueRepository->getByTypeId(
                $this->slave->getTypeId(),
                $id,
                $this->slave->getId(),
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

    /**
     * @param array $changedLeds
     * @return array
     */
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
     * @param int $id
     * @param array $led
     * @throws SelectError
     * @throws SaveError
     */
    private function saveLed(int $id, array $led): void
    {
        foreach ($led as $attribute => $value) {
            if (!in_array($attribute, self::ATTRIBUTES)) {
                continue;
            }

            (new ValueModel())
                ->setAttribute($this->getLedAttribute($id, $attribute))
                ->setOrder(0)
                ->setValue($value)
                ->save()
            ;
        }
    }

    /**
     * @param int $id
     * @param string $key
     * @return Attribute
     * @throws SaveError
     * @throws SelectError
     */
    private function getLedAttribute(int $id, string $key): Attribute
    {
        if (!isset($this->ledsAttributes[$id])) {
            $this->ledsAttributes[$id] = [];
        } else if (!isset($this->ledsAttributes[$id][$key])) {
            $this->addAttribute($id, $key);
        }

        if (isset($this->ledsAttributes[$id][$key])) {
            return $this->ledsAttributes[$id][$key];
        }

        foreach ($this->attributeRepository->getByModule($this->slave, $id, null, self::ATTRIBUTE_TYPE) as $attribute) {
            $this->ledsAttributes[$id][$attribute->getKey()] = $attribute;
        }

        if (!isset($this->ledsAttributes[$id][$key])) {
            $this->addAttribute($id, $key);
        }

        return $this->ledsAttributes[$id][$key];
    }

    /**
     * @param int $id
     * @param string $key
     * @throws SaveError
     */
    private function addAttribute(int $id, string $key): void
    {
        $this->ledsAttributes[$id][$key] = (new Attribute())
            ->setTypeId($this->slave->getTypeId())
            ->setModuleId($this->slave->getId())
            ->setSubId($id)
            ->setKey($key)
            ->setType(self::ATTRIBUTE_TYPE)
        ;
        $this->ledsAttributes[$id][$key]->save();
    }

    /**
     * @param array $leds
     * @throws DeleteError
     */
    public function deleteUnusedLeds(array $leds)
    {
        if (count($leds)) {
            ksort($leds);
            $leds = array_keys($leds);
            $id = end($leds);
        } else {
            $id = -1;
        }

        $this->attributeRepository->deleteWithBiggerSubIds(
            $this->slave,
            $id,
            null,
            self::ATTRIBUTE_TYPE
        );
    }

    /**
     * @param array $leds
     * @return array
     * @throws OutOfRangeException
     */
    public function getChannelCounts(array $leds): array
    {
        $counts = [];

        foreach ($leds as $led) {
            if (!isset($counts[$led[self::ATTRIBUTE_KEY_CHANNEL]])) {
                $counts[$led[self::ATTRIBUTE_KEY_CHANNEL]] = 0;
            }

            $counts[$led[self::ATTRIBUTE_KEY_CHANNEL]]++;
        }

        return $counts;
    }

    /**
     * @param int $id
     * @param string|null $key
     * @return ValueModel[]
     * @throws Exception
     */
    public function getById(int $id, string $key = null): array
    {
        return $this->valueRepository->getByTypeId(
            $this->slave->getTypeId(),
            $id,
            $this->slave->getId(),
            self::ATTRIBUTE_TYPE,
            $key ?? false
        );
    }

    /**
     * @param int $id
     * @return int
     * @throws OutOfRangeException
     */
    private function getChannelById(int $id): int
    {
        $config = Json::decode($this->slave->getConfig());
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