<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Attribute\Neopixel;

use Exception;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\Neopixel\Led;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value as ValueModel;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use JsonException;
use OutOfRangeException;
use Psr\Log\LoggerInterface;
use ReflectionException;

class LedService
{
    public const ATTRIBUTE_TYPE = 'led';

    public const ATTRIBUTE_KEY_NUMBER = 'number';

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

    /**
     * @var Attribute[][]
     */
    private array $ledsAttributes = [];

    public function __construct(
        private AttributeRepository $attributeRepository,
        private ValueRepository $valueRepository,
        private LoggerInterface $logger,
        private ModelManager $modelManager
    ) {
    }

    /**
     * @param Led[] $leds
     *
     * @throws SaveError
     */
    public function saveLeds(Module $slave, array $leds): void
    {
        $this->ledsAttributes = [];
        $this->attributeRepository->startTransaction();

        try {
            foreach ($leds as $led) {
                $this->saveLed($slave, $led);
            }
        } catch (SaveError $exception) {
            $this->attributeRepository->rollback();

            throw $exception;
        }

        $this->attributeRepository->commit();
    }

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
    public function getActualState(Module $slave): array
    {
        $actualLeds = [];
        $config = JsonUtility::decode($slave->getConfig() ?? '[]');

        for ($i = 0; $i < array_sum($config[NeopixelService::CONFIG_COUNTS]); ++$i) {
            $led = (new Led())->setNumber($i);

            foreach ($this->getById($slave, $i) as $attributeValue) {
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
     * @throws SaveError
     */
    private function saveLed(Module $slave, Led $led): void
    {
        foreach ($led->jsonSerialize() as $attribute => $value) {
            if (!in_array($attribute, self::ATTRIBUTES)) {
                continue;
            }

            $this->logger->debug(sprintf(
                'Set LED %d attribute %s with value %s!',
                $led->getNumber(),
                $attribute,
                (string) $value
            ));

            $this->modelManager->save(
                (new ValueModel())
                    ->setAttribute($this->getLedAttribute($slave, $led->getNumber(), $attribute))
                    ->setOrder(0)
                    ->setValue((string) (
                        ($attribute === self::ATTRIBUTE_KEY_LEFT || $attribute === self::ATTRIBUTE_KEY_TOP) && $value < 0
                            ? 0
                            : $value
                    ))
            );
        }
    }

    /**
     * @throws SaveError
     * @throws Exception
     */
    private function getLedAttribute(Module $slave, int $id, string $key): Attribute
    {
        if (!isset($this->ledsAttributes[$id])) {
            $this->ledsAttributes[$id] = [];
        }

        if (isset($this->ledsAttributes[$id][$key])) {
            return $this->ledsAttributes[$id][$key];
        }

        try {
            foreach ($this->attributeRepository->getByModule($slave, $id, null, self::ATTRIBUTE_TYPE) as $attribute) {
                $this->ledsAttributes[$id][$attribute->getKey()] = $attribute;
            }
        } catch (SelectError) {
            // No Attributes
        }

        if (!isset($this->ledsAttributes[$id][$key])) {
            $this->addAttribute($slave, $id, $key);
        }

        return $this->ledsAttributes[$id][$key];
    }

    /**
     * @throws JsonException
     * @throws SaveError
     * @throws ReflectionException
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
        $this->modelManager->save($this->ledsAttributes[$id][$key]);
    }

    /**
     * @param Led[] $leds
     *
     * @throws DeleteError
     */
    public function deleteUnusedLeds(Module $slave, array $leds)
    {
        $number = -1;

        foreach ($leds as $led) {
            if ($number > $led->getNumber()) {
                continue;
            }

            $number = $led->getNumber();
        }

        $this->attributeRepository->deleteWithBiggerSubIds(
            $slave,
            $number,
            null,
            self::ATTRIBUTE_TYPE
        );
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
