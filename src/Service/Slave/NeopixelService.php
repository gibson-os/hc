<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use Exception;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Dto\Neopixel\Led;
use GibsonOS\Module\Hc\Event\NeopixelEvent;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Mapper\LedMapper;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\Module\Hc\Store\Neopixel\LedStore;
use JsonException;
use LogicException;
use Psr\Log\LoggerInterface;

class NeopixelService extends AbstractHcSlave
{
    public const COMMAND_SET_LEDS = 0;

    public const COMMAND_LED_COUNTS = 1;

    public const COMMAND_CHANNEL_WRITE = 2;

    public const COMMAND_SEQUENCE_START = 10;

    public const COMMAND_SEQUENCE_PAUSE = 11;

    public const COMMAND_SEQUENCE_STOP = 12;

    public const COMMAND_SEQUENCE_EEPROM_ADDRESS = 13;

    public const COMMAND_SEQUENCE_NEW = 14;

    public const COMMAND_SEQUENCE_ADD_STEP = 15;

    public const COMMAND_CONFIGURATION_READ_LENGTH = 3;

    private const CONFIG_CHANNELS = 'channels';

    private const CONFIG_MAX_LEDS = 'maxLeds';

    public const CONFIG_COUNTS = 'counts';

    public function __construct(
        MasterService $masterService,
        TransformService $transformService,
        EventService $eventService,
        private LedMapper $ledMapper,
        private LedService $ledService,
        private LedStore $ledStore,
        ModuleRepository $moduleRepository,
        TypeRepository $typeRepository,
        MasterRepository $masterRepository,
        LogRepository $logRepository,
        SlaveFactory $slaveFactory,
        LoggerInterface $logger,
        ModelManager $modelManager
    ) {
        parent::__construct(
            $masterService,
            $transformService,
            $eventService,
            $moduleRepository,
            $typeRepository,
            $masterRepository,
            $logRepository,
            $slaveFactory,
            $logger,
            $modelManager
        );
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws DeleteError
     * @throws ReceiveError
     * @throws SaveError
     * @throws Exception
     */
    public function slaveHandshake(Module $module): Module
    {
        if ($module->getConfig() === null) {
            $config = $this->getConfig($module);
            $module->setConfig(JsonUtility::encode($config));
        } else {
            $config = JsonUtility::decode($module->getConfig() ?? '[]');
        }

        $config[self::CONFIG_COUNTS] = $this->readLedCounts($module);
        $module->setConfig(JsonUtility::encode($config));
        $this->modelManager->save($module);

        $id = 0;
        $leds = [];

        foreach ($config[self::CONFIG_COUNTS] as $channel => $count) {
            for ($i = 0; $i < $count; ++$i) {
                $top = $this->ledService->getById($module, $id, LedService::ATTRIBUTE_KEY_TOP);
                $left = $this->ledService->getById($module, $id, LedService::ATTRIBUTE_KEY_LEFT);

                $leds[$id] = (new Led())
                    ->setNumber($id)
                    ->setChannel($channel)
                    ->setTop(count($top) === 1 ? (int) $top[0]->getValue() : ((int) $channel * 3))
                    ->setLeft(count($left) === 1 ? (int) $left[0]->getValue() : ($i * 3))
                ;
                ++$id;
            }
        }

        $this->ledService->deleteUnusedLeds($module, $leds);
        $this->ledService->saveLeds($module, $leds);

        return $module;
    }

    public function receive(Module $module, BusMessage $busMessage): void
    {
        // TODO: Implement receive() method.
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws WriteException
     * @throws JsonException
     */
    public function onOverwriteExistingSlave(Module $module, Module $existingSlave): Module
    {
        if (empty($existingSlave->getId())) {
            throw new GetError('Keine ID vorhanden!');
        }

        $existingConfig = JsonUtility::decode($existingSlave->getConfig() ?? 'null');
        $maxUsedChannel = 0;
        $usedLedsCount = 0;

        foreach ($existingConfig[self::CONFIG_COUNTS] as $channel => $count) {
            if ($count === 0) {
                continue;
            }

            $maxUsedChannel = $channel;
            $usedLedsCount += $count;
        }

        $config = $this->getConfig($module);

        if ($config[self::CONFIG_CHANNELS] < $maxUsedChannel) {
            throw new LogicException(
                'Slave hat ' . $config[self::CONFIG_CHANNELS] . ' Kanäle. ' .
                'Benötig werden ' . $maxUsedChannel . ' Kanäle.'
            );
        }

        if ($config[self::CONFIG_MAX_LEDS] < $usedLedsCount) {
            throw new LogicException(
                'Slave hat ' . $config[self::CONFIG_MAX_LEDS] . ' LEDs. ' .
                'Benötig werden ' . $usedLedsCount . ' LEDs.'
            );
        }

        $config[self::CONFIG_COUNTS] = $existingConfig[self::CONFIG_COUNTS];
        $this->writeLedCounts($module, $config[self::CONFIG_COUNTS]);

        $this->ledStore->setModule($existingSlave);
        $this->writeSetLeds($module, $this->ledStore->getList());
        $channels = [];

        for ($channel = 0; $channel < $config[self::CONFIG_CHANNELS]; ++$channel) {
            $channels[$channel] = $config[self::CONFIG_COUNTS][$channel];
        }

        $this->writeChannels($module, $channels);
        $module->setConfig(JsonUtility::encode($config));

        return $module;
    }

    /**
     * @param Led[] $leds
     *
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function writeSetLeds(Module $slave, array $leds): NeopixelService
    {
        $data = $this->ledMapper->mapToStrings($leds, (int) $slave->getBufferSize());

        foreach ($this->getWriteStrings($slave, $data) as $writeString) {
            $this->write($slave, self::COMMAND_SET_LEDS, $writeString);
        }

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     * @throws JsonException
     */
    public function writeChannel(Module $slave, int $channel, int $length = 0): NeopixelService
    {
        return $this->writeChannels($slave, [$channel => $length]);
    }

    /**
     * @param int[] $channelsLength
     *
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     * @throws JsonException
     */
    public function writeChannels(Module $slave, array $channelsLength): NeopixelService
    {
        $config = JsonUtility::decode((string) $slave->getConfig());

        for ($channel = 0; $channel < $config[self::CONFIG_CHANNELS]; ++$channel) {
            if (isset($channelsLength[$channel])) {
                continue;
            }

            $channelsLength[$channel] = 0;
        }

        if (
            empty($channelsLength) ||
            max($channelsLength) === 0
        ) {
            throw new WriteException('No channels set!');
        }

        ksort($channelsLength);

        $this->write(
            $slave,
            self::COMMAND_CHANNEL_WRITE,
            implode('', array_map(static function ($length) {
                if ($length === null) {
                    $length = 0;
                }

                return chr($length >> 8) . chr($length & 255);
            }, $channelsLength))
        );

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSequenceStart(Module $slave, int $iterations = 0): NeopixelService
    {
        $this->write($slave, self::COMMAND_SEQUENCE_START, chr($iterations));

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSequenceStop(Module $slave): NeopixelService
    {
        $this->write($slave, self::COMMAND_SEQUENCE_STOP);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSequencePause(Module $slave): NeopixelService
    {
        $this->write($slave, self::COMMAND_SEQUENCE_PAUSE);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSequenceEepromAddress(Module $slave, int $address): NeopixelService
    {
        $this->write(
            $slave,
            self::COMMAND_SEQUENCE_EEPROM_ADDRESS,
            chr($address >> 8) . chr($address & 255)
        );

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readSequenceEepromAddress(Module $slave): int
    {
        return $this->transformService->asciiToUnsignedInt($this->read(
            $slave,
            self::COMMAND_SEQUENCE_EEPROM_ADDRESS,
            2
        ));
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSequenceNew(Module $slave): NeopixelService
    {
        $this->write($slave, self::COMMAND_SEQUENCE_NEW);

        return $this;
    }

    /**
     * @param Led[] $leds
     *
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function writeSequenceAddStep(Module $slave, int $runtime, array $leds): NeopixelService
    {
        $dataStrings = $this->ledMapper->mapToStrings($leds, (int) $slave->getBufferSize());
        $writeStrings = $this->getWriteStrings($slave, $dataStrings);

        foreach ($writeStrings as $index => $writeString) {
            $actualRuntime = count($writeStrings) - 1 === $index ? $runtime : 0;

            $this->write(
                $slave,
                self::COMMAND_SEQUENCE_ADD_STEP,
                chr(($actualRuntime >> 8) & 255) . chr($actualRuntime & 255) . $writeString
            );
        }

        return $this;
    }

    /**
     * @throws ReceiveError
     * @throws SaveError
     * @throws JsonException
     * @throws AbstractException
     *
     * @return int[]
     */
    public function readLedCounts(Module $slave): array
    {
        $config = JsonUtility::decode((string) $slave->getConfig());
        $counts = $this->read($slave, self::COMMAND_LED_COUNTS, $config['channels'] * 2);
        $channelCounts = [];
        $position = 0;

        for ($i = 0; $i < $config['channels']; ++$i) {
            $channelCounts[$i] = $this->transformService->asciiToUnsignedInt(substr($counts, $position, 2));
            $position += 2;
        }

        return $channelCounts;
    }

    /**
     * @param int[] $counts
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeLedCounts(Module $slave, array $counts): NeopixelService
    {
        $data = '';

        foreach ($counts as $count) {
            $data .= chr($count >> 8) . chr($count & 255);
        }

        $this->write($slave, self::COMMAND_LED_COUNTS, $data);

        return $this;
    }

    /**
     * @param Led[] $leds
     *
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     * @throws Exception
     */
    public function writeLeds(Module $slave, array $leds): void
    {
        $changedSlaveLeds = $this->ledService->getChanges($this->ledService->getActualState($slave), $leds);
        $this->writeSetLeds($slave, array_intersect_key($leds, $changedSlaveLeds));
        $lastChangedIds = $this->ledService->getLastIds($slave, $changedSlaveLeds);

        if (empty($lastChangedIds)) {
            $startCount = 0;
            $lastChangedIds = array_map(static function (int $count) use (&$startCount) {
                if ($count === 0) {
                    return -1;
                }

                $startCount += $count;

                return $startCount - 1;
            }, JsonUtility::decode($slave->getConfig() ?: JsonUtility::encode(['counts' => 0]))['counts']);
        }

        $this->ledService->saveLeds($slave, $leds);
        $this->writeChannels(
            $slave,
            array_map(
                fn ($lastChangedId) => $this->ledService->getNumberById($slave, $lastChangedId) + 1,
                $lastChangedIds
            )
        );
    }

    /**
     * @param string[] $data
     *
     * @throws WriteException
     *
     * @return string[]
     */
    private function getWriteStrings(Module $slave, array $data): array
    {
        $writeStrings = [];
        $bufferSize = $slave->getBufferSize();

        while (!empty($data)) {
            $dataString = '';

            foreach ($data as $key => $string) {
                if (strlen($string) > $bufferSize) {
                    throw new WriteException(sprintf(
                        'Write string has a length of %d. Max allowed length is %d',
                        strlen($string),
                        $bufferSize ?? 0
                    ));
                }

                if (strlen($dataString) + strlen($string) > $bufferSize) {
                    continue;
                }

                $dataString .= $string;
                unset($data[$key]);
            }

            $writeStrings[] = $dataString;
        }

        return $writeStrings;
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    private function getConfig(Module $slave): array
    {
        $config = $this->readConfig($slave, self::COMMAND_CONFIGURATION_READ_LENGTH);
        $config = [
            self::CONFIG_CHANNELS => $this->transformService->asciiToUnsignedInt($config, 0),
            self::CONFIG_MAX_LEDS => $this->transformService->asciiToUnsignedInt(substr($config, 1)),
            self::CONFIG_COUNTS => [],
        ];

        return $config;
    }

    protected function getEventClassName(): string
    {
        return NeopixelEvent::class;
    }
}
