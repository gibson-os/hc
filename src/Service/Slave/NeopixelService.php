<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use Exception;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService;
use GibsonOS\Module\Hc\Service\EventService;
use GibsonOS\Module\Hc\Service\Formatter\NeopixelFormatter;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\Module\Hc\Store\Neopixel\LedStore;
use LogicException;

class NeopixelService extends AbstractHcSlave
{
    private const COMMAND_SET_LEDS = 0;

    private const COMMAND_LED_COUNTS = 1;

    private const COMMAND_CHANNEL_WRITE = 2;

    private const COMMAND_CHANNEL_STATUS = 3;

    private const COMMAND_SEQUENCE_START = 10;

    private const COMMAND_SEQUENCE_PAUSE = 11;

    private const COMMAND_SEQUENCE_EEPROM_ADDRESS = 12;

    private const COMMAND_SEQUENCE_NEW = 13;

    private const COMMAND_SEQUENCE_ADD_STEP = 14;

    private const COMMAND_CONFIGURATION_READ_LENGTH = 3;

    private const CHANNEL_READ_STATUS_NOT_SET = 254;

    private const CHANNEL_READ_STATUS_NO_LEDS = 255;

    private const CONFIG_CHANNELS = 'channels';

    private const CONFIG_MAX_LEDS = 'maxLeds';

    private const CONFIG_COUNTS = 'counts';

    /**
     * @var LedService
     */
    private $ledAttribute;

    /**
     * @var NeopixelFormatter
     */
    private $formatter;

    public function __construct(
        MasterService $master,
        TransformService $transform,
        EventService $event,
        NeopixelFormatter $formatter,
        LedService $ledAttribute
    ) {
        parent::__construct($master, $transform, $event);
        $this->ledAttribute = $ledAttribute;
        $this->formatter = $formatter;
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     * @throws DateTimeError
     * @throws GetError
     * @throws DeleteError
     * @throws Exception
     */
    public function handshake(Module $slave): Module
    {
        parent::handshake($slave);

        if ($slave->getConfig() === null) {
            $config = $this->getConfig($slave);
            $slave->setConfig(JsonUtility::encode($config));
        } else {
            $config = JsonUtility::decode($slave->getConfig());
        }

        $config[self::CONFIG_COUNTS] = $this->readLedCounts($slave);
        $slave->setConfig(JsonUtility::encode($config));
        $slave->save();

        $id = 0;
        $leds = [];

        foreach ($config[self::CONFIG_COUNTS] as $channel => $count) {
            for ($i = 0; $i < $count; ++$i) {
                $top = $this->ledAttribute->getById($slave, $id, LedService::ATTRIBUTE_KEY_TOP);
                $left = $this->ledAttribute->getById($slave, $id, LedService::ATTRIBUTE_KEY_LEFT);
                $leds[$id] = [
                    LedService::ATTRIBUTE_KEY_CHANNEL => $channel,
                    LedService::ATTRIBUTE_KEY_RED => 0,
                    LedService::ATTRIBUTE_KEY_GREEN => 0,
                    LedService::ATTRIBUTE_KEY_BLUE => 0,
                    LedService::ATTRIBUTE_KEY_FADE_IN => 0,
                    LedService::ATTRIBUTE_KEY_BLINK => 0,
                    LedService::ATTRIBUTE_KEY_TOP => count($top) === 1 ? (int) $top[0]->getValue() : ($channel * 3),
                    LedService::ATTRIBUTE_KEY_LEFT => count($left) === 1 ? (int) $left[0]->getValue() : ($i * 3),
                ];
                ++$id;
            }
        }

        $this->ledAttribute->deleteUnusedLeds($slave, $leds);
        $this->ledAttribute->saveLeds($slave, $leds);

        return $slave;
    }

    public function receive(Module $slave, int $type, int $command, string $data): void
    {
        // TODO: Implement receive() method.
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function onOverwriteExistingSlave(Module $slave, Module $existingSlave): Module
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

        $config = $this->getConfig($slave);

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
        $this->writeLedCounts($slave, $config[self::CONFIG_COUNTS]);

        $ledStore = new LedStore();
        $ledStore->setModule($existingSlave->getId());
        $list = $ledStore->getList();
        $this->writeSetLeds($slave, $list);

        for ($channel = 0; $channel < $config[self::CONFIG_CHANNELS]; ++$channel) {
            $this->writeChannel($slave, $channel);
        }

        $slave->setConfig(JsonUtility::encode($config));

        return $slave;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSetLeds(Module $slave, array $leds): NeopixelService
    {
        $data = $this->formatter->getLedsAsStrings($leds, (int) $slave->getDataBufferSize());

        foreach ($this->getWriteStrings($slave, $data) as $writeString) {
            $this->write($slave, self::COMMAND_SET_LEDS, $writeString);
        }

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeChannel(Module $slave, int $channel, int $length = 0): NeopixelService
    {
        $this->write(
            $slave,
            self::COMMAND_CHANNEL_WRITE,
            chr($channel) . chr($length >> 8) . chr($length & 255)
        );

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeChannelStatus(Module $slave, int $channel, int $startAddress, int $length): NeopixelService
    {
        $this->write(
            $slave,
            self::COMMAND_CHANNEL_STATUS,
            chr($channel) .
            chr($startAddress >> 8) .
            chr($startAddress & 255) .
            chr($length)
        );

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readChannelStatus(Module $slave, int $length): array
    {
        $data = $this->read($slave, self::COMMAND_CHANNEL_STATUS, $length);
        $firstByte = $this->transform->asciiToInt($data, 0);

        if ($firstByte === self::CHANNEL_READ_STATUS_NOT_SET) {
            throw new ReceiveError('Es ist kein Channel gesetzt!', self::CHANNEL_READ_STATUS_NOT_SET);
        }

        if ($firstByte === self::CHANNEL_READ_STATUS_NO_LEDS) {
            throw new ReceiveError('Es existiert keine LED!', self::CHANNEL_READ_STATUS_NO_LEDS);
        }

        return $this->formatter->getLedsAsArray($data);
    }

    /**
     * @param int $repeat
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSequenceStart(Module $slave, $repeat = 255): NeopixelService
    {
        $this->write($slave, self::COMMAND_SEQUENCE_START, chr($repeat));

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
        return $this->transform->asciiToInt($this->read(
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
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSequenceAddStep(Module $slave, int $runtime, array $leds): NeopixelService
    {
        $dataStrings = $this->formatter->getLedsAsStrings($leds, (int) $slave->getDataBufferSize());

        foreach ($this->getWriteStrings($slave, $dataStrings) as $writeString) {
            $this->write($slave, self::COMMAND_SEQUENCE_ADD_STEP, $writeString);
        }

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
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
            $channelCounts[$i] = $this->transform->asciiToInt(substr($counts, $position, 2));
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

    public function getLedAttribute(): LedService
    {
        return $this->ledAttribute;
    }

    /**
     * @param string[] $data
     *
     * @return string[]
     */
    private function getWriteStrings(Module $slave, array $data): array
    {
        $writeStrings = [];
        $bufferSize = $slave->getDataBufferSize();

        while (!empty($data)) {
            $dataString = '';

            foreach ($data as $key => $string) {
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
            self::CONFIG_CHANNELS => $this->transform->asciiToInt($config, 0),
            self::CONFIG_MAX_LEDS => $this->transform->asciiToInt(substr($config, 1)),
            self::CONFIG_COUNTS => [],
        ];

        return $config;
    }
}
