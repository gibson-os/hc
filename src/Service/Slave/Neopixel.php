<?php
namespace GibsonOS\Module\Hc\Service\Slave;

use Exception;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Utility\Json;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\Event;
use GibsonOS\Module\Hc\Service\Master;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\Led as LedAttribute;
use GibsonOS\Module\Hc\Store\Neopixel\Led as LedStore;
use GibsonOS\Module\Hc\Utility\Formatter\Neopixel as NeopixelFormatter;
use GibsonOS\Module\Hc\Utility\Transform;
use LogicException;

class Neopixel extends AbstractHcSlave
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
     * @var LedAttribute
     */
    private $ledAttribute;

    public function __construct(Module $slaveModel, Master $master, Event $event, array $attributes = [])
    {
        parent::__construct($slaveModel, $master, $event, $attributes);
        $this->ledAttribute = $this->attributes[LedAttribute::class];
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     * @throws Exception
     */
    public function handshake(): void
    {
        parent::handshake();

        if (is_null($this->slave->getConfig())) {
            $config = $this->getConfig();
            $this->slave->setConfig(Json::encode($config));
        } else {
            $config = Json::decode($this->slave->getConfig());
        }

        $config[self::CONFIG_COUNTS] = $this->readLedCounts();
        $this->slave->setConfig(Json::encode($config));
        $this->slave->save();

        $id = 0;
        $leds = [];

        foreach ($config[self::CONFIG_COUNTS] as $channel => $count) {
            for ($i = 0; $i < $count; $i++) {
                $top = $this->ledAttribute->getById($id, LedAttribute::ATTRIBUTE_KEY_TOP);
                $left = $this->ledAttribute->getById($id, LedAttribute::ATTRIBUTE_KEY_LEFT);
                $leds[$id] = [
                    LedAttribute::ATTRIBUTE_KEY_CHANNEL => $channel,
                    LedAttribute::ATTRIBUTE_KEY_RED => 0,
                    LedAttribute::ATTRIBUTE_KEY_GREEN => 0,
                    LedAttribute::ATTRIBUTE_KEY_BLUE => 0,
                    LedAttribute::ATTRIBUTE_KEY_FADE_IN => 0,
                    LedAttribute::ATTRIBUTE_KEY_BLINK => 0,
                    LedAttribute::ATTRIBUTE_KEY_TOP => count($top) === 1 ? (int) $top[0]->getValue() : ($channel * 3),
                    LedAttribute::ATTRIBUTE_KEY_LEFT => count($left) === 1 ? (int) $left[0]->getValue() : ($i * 3)
                ];
                $id++;
            }
        }

        $this->ledAttribute->deleteUnusedLeds($leds);
        $this->ledAttribute->saveLeds($leds);
    }

    /**
     * @param Module $existingSlave
     * @throws AbstractException
     */
    public function onOverwriteExistingSlave(Module $existingSlave): void
    {
        $existingConfig = Json::decode($existingSlave->getConfig());
        $maxUsedChannel = 0;
        $usedLedsCount = 0;

        foreach ($existingConfig[self::CONFIG_COUNTS] as $channel => $count) {
            if ($count === 0) {
                continue;
            }

            $maxUsedChannel = $channel;
            $usedLedsCount += $count;
        }

        $config = $this->getConfig();

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
        $this->writeLedCounts($config[self::CONFIG_COUNTS]);

        $ledStore = new LedStore();
        $ledStore->setModule($existingSlave->getId());
        $list = $ledStore->getList();
        $this->writeSetLeds($list);

        for ($channel = 0; $channel < $config[self::CONFIG_CHANNELS]; $channel++) {
            $this->writeChannel($channel);
        }

        $this->slave->setConfig(Json::encode($config));
    }

    /**
     * @param array $leds
     * @return Neopixel
     * @throws AbstractException
     */
    public function writeSetLeds(array $leds): Neopixel
    {
        $data = NeopixelFormatter::getLedsAsStrings($leds, $this->slave->getDataBufferSize());

        foreach ($this->getWriteStrings($data) as $writeString) {
            $this->write(self::COMMAND_SET_LEDS, $writeString);
        }

        return $this;
    }

    /**
     * @param int $channel
     * @param int $length
     * @return Neopixel
     * @throws AbstractException
     */
    public function writeChannel(int $channel, int $length = 0): Neopixel
    {
        $this->write(
            self::COMMAND_CHANNEL_WRITE,
            chr($channel) . chr($length >> 8) . chr($length & 255)
        );

        return $this;
    }

    /**
     * @param int $channel
     * @param int $startAddress
     * @param int $length
     * @return Neopixel
     * @throws AbstractException
     */
    public function writeChannelStatus(int $channel, int $startAddress, int $length): Neopixel
    {
        $this->write(
            self::COMMAND_CHANNEL_STATUS,
            chr($channel) .
            chr($startAddress >> 8) .
            chr($startAddress & 255) .
            chr($length)
        );

        return $this;
    }

    /**
     * @param int $length
     * @return array
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readChannelStatus(int $length): array
    {
        $data = $this->read(self::COMMAND_CHANNEL_STATUS, $length);
        $firstByte = Transform::asciiToInt($data, 0);

        if ($firstByte === self::CHANNEL_READ_STATUS_NOT_SET) {
            throw new ReceiveError('Es ist kein Channel gesetzt!', self::CHANNEL_READ_STATUS_NOT_SET);
        }

        if ($firstByte === self::CHANNEL_READ_STATUS_NO_LEDS) {
            throw new ReceiveError('Es existiert keine LED!', self::CHANNEL_READ_STATUS_NO_LEDS);
        }

        return NeopixelFormatter::getLedsAsArray($data);
    }

    /**
     * @param int $repeat
     * @return Neopixel
     * @throws AbstractException
     */
    public function writeSequenceStart($repeat = 255): Neopixel
    {
        $this->write(self::COMMAND_SEQUENCE_START, chr($repeat));

        return $this;
    }

    /**
     * @return Neopixel
     * @throws AbstractException
     */
    public function writeSequencePause(): Neopixel
    {
        $this->write(self::COMMAND_SEQUENCE_PAUSE);

        return $this;
    }

    /**
     * @param int $address
     * @return Neopixel
     * @throws AbstractException
     */
    public function writeSequenceEepromAddress(int $address): Neopixel
    {
        $this->write(
            self::COMMAND_SEQUENCE_EEPROM_ADDRESS,
            chr($address >> 8) . chr($address & 255)
        );

        return $this;
    }

    /**
     * @return int
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readSequenceEepromAddress(): int
    {
        return Transform::asciiToInt($this->read(self::COMMAND_SEQUENCE_EEPROM_ADDRESS, 2));
    }

    /**
     * @return Neopixel
     * @throws AbstractException
     */
    public function writeSequenceNew(): Neopixel
    {
        $this->write(self::COMMAND_SEQUENCE_NEW);

        return $this;
    }

    /**
     * @param int $runtime
     * @param array $leds
     * @return Neopixel
     * @throws AbstractException
     */
    public function writeSequenceAddStep(int $runtime, array $leds): Neopixel
    {
        $dataStrings = NeopixelFormatter::getLedsAsStrings($leds, $this->slave->getDataBufferSize());

        foreach ($this->getWriteStrings($dataStrings) as $writeString) {
            $this->write(self::COMMAND_SEQUENCE_ADD_STEP, $writeString);
        }

        return $this;
    }

    /**
     * @return int[]
     * @throws AbstractException
     * @throws ReceiveError
     */
    public function readLedCounts(): array
    {
        $config = Json::decode($this->slave->getConfig());
        $counts = $this->read(self::COMMAND_LED_COUNTS, $config['channels'] * 2);
        $channelCounts = [];
        $position = 0;

        for ($i = 0; $i < $config['channels']; $i++) {
            $channelCounts[$i] = Transform::asciiToInt(substr($counts, $position, 2));
            $position += 2;
        }

        return $channelCounts;
    }

    /**
     * @param int[] $counts
     * @return Neopixel
     * @throws AbstractException
     */
    public function writeLedCounts(array $counts): Neopixel
    {
        $data = null;

        foreach ($counts as $count) {
            $data .= chr($count >> 8) . chr($count & 255);
        }

        $this->write(self::COMMAND_LED_COUNTS, $data);

        return $this;
    }

    /**
     * @return LedAttribute
     */
    public function getLedAttribute(): LedAttribute
    {
        return $this->ledAttribute;
    }

    /**
     * @param string[] $data
     * @return string[]
     */
    private function getWriteStrings(array $data): array
    {
        $writeStrings = [];
        $bufferSize = $this->slave->getDataBufferSize();

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
     * @return array|string
     * @throws AbstractException
     * @throws ReceiveError
     */
    private function getConfig()
    {
        $config = $this->readConfig(self::COMMAND_CONFIGURATION_READ_LENGTH);
        $config = [
            self::CONFIG_CHANNELS => Transform::asciiToInt($config, 0),
            self::CONFIG_MAX_LEDS => Transform::asciiToInt(substr($config, 1)),
            self::CONFIG_COUNTS => []
        ];

        return $config;
    }
}