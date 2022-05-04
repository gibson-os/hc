<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Attribute\Event;
use GibsonOS\Core\Dto\Parameter\IntParameter;
use GibsonOS\Core\Dto\Parameter\OptionParameter;
use GibsonOS\Core\Dto\Parameter\StringParameter;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Manager\ReflectionManager;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\Neopixel\Led;
use GibsonOS\Module\Hc\Dto\Parameter\ModuleParameter;
use GibsonOS\Module\Hc\Dto\Parameter\Neopixel\ImageParameter;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Mapper\LedMapper;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use JsonException;
use Psr\Log\LoggerInterface;
use ReflectionException;

#[Event('Neopixel')]
#[Event\Listener('sequence', 'module', ['params' => [
    'paramKey' => 'moduleId',
    'recordKey' => 'id',
]])]
#[Event\ParameterOption('slave', 'typeHelper', 'neopixel')]
#[Event\ParameterOption('module', 'typeHelper', 'neopixel')]
class NeopixelEvent extends AbstractHcEvent
{
    public function __construct(
        EventService $eventService,
        ReflectionManager $reflectionManager,
        TypeRepository $typeRepository,
        LoggerInterface $logger,
        private NeopixelService $neopixelService,
        private LedMapper $ledMapper,
        private AttributeRepository $attributeRepository,
    ) {
        parent::__construct($eventService, $reflectionManager, $typeRepository, $logger, $this->neopixelService);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('LEDs setzen')]
    public function writeSetLeds(
        #[Event\Parameter(ModuleParameter::class)] Module $module,
        array $leds
    ): void {
        $this->neopixelService->writeLeds(
            $module,
            $this->ledMapper->mapFromArrays($module, $leds, true, false)
        );
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     * @throws JsonException
     */
    #[Event\Method('Channel schreiben')]
    public function writeChannel(
        #[Event\Parameter(ModuleParameter::class)] Module $module,
        #[Event\Parameter(IntParameter::class, 'Channel')] int $channel,
        #[Event\Parameter(IntParameter::class, 'Länge')] int $length = 0
    ): void {
        $this->neopixelService->writeChannel($module, $channel, $length);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Sequenz starten')]
    public function writeSequenceStart(
        #[Event\Parameter(ModuleParameter::class)] Module $module,
        #[Event\Parameter(IntParameter::class, 'Wiederholungen')] int $repeat = 0
    ): void {
        $this->neopixelService->writeSequenceStart($module, $repeat);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Sequenz stoppen')]
    public function writeSequenceStop(
        #[Event\Parameter(ModuleParameter::class)] Module $module
    ): void {
        $this->neopixelService->writeSequenceStop($module);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Sequenz pausieren')]
    public function writeSequencePause(
        #[Event\Parameter(ModuleParameter::class)] Module $module
    ): void {
        $this->neopixelService->writeSequencePause($module);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Sequenz EEPROM Adresse schreiben')]
    public function writeSequenceEepromAddress(
        #[Event\Parameter(ModuleParameter::class)] Module $module,
        #[Event\Parameter(IntParameter::class, 'Adresse')] int $address
    ): void {
        $this->neopixelService->writeSequenceEepromAddress($module, $address);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Sequenz EEPROM Adresse lesen')]
    #[Event\ReturnValue(IntParameter::class, 'EEPROM Adresse')]
    public function readSequenceEepromAddress(
        #[Event\Parameter(ModuleParameter::class)] Module $module
    ): int {
        return $this->neopixelService->readSequenceEepromAddress($module);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Neue Sequenz übertragen')]
    public function writeSequenceNew(
        #[Event\Parameter(ModuleParameter::class)] Module $module
    ): void {
        $this->neopixelService->writeSequenceNew($module);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws ReceiveError
     * @throws JsonException
     */
    #[Event\Method('LED Anzahl lesen')]
//    #[Event\ReturnValue(className: CollectionParameter::class, options: [
//        'className' => [IntParameter::class],
//        'range' => [1, LedMapper::MAX_PROTOCOL_LEDS + 1],
//    ])]
    public function readLedCounts(
        #[Event\Parameter(ModuleParameter::class)] Module $module
    ): array {
        return $this->neopixelService->readLedCounts($module);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('LED Anzahl schreiben')]
    public function writeLedCounts(
        #[Event\Parameter(ModuleParameter::class)] Module $module,
//        #[Event\Parameter(className: CollectionParameter::class, options: [
//            'className' => [IntParameter::class],
//            'range' => [1, LedMapper::MAX_PROTOCOL_LEDS + 1],
//        ])] array $counts
         array $counts
    ): void {
        $this->neopixelService->writeLedCounts($module, $counts);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     * @throws JsonException
     */
    #[Event\Method('Bild anzeigen')]
    #[Event\Listener('sequence', 'slave', ['params' => [
        'paramKey' => 'moduleId',
        'recordKey' => 'id',
    ]])]
    public function sendImage(
        #[Event\Parameter(ModuleParameter::class)] Module $module,
        #[Event\Parameter(ImageParameter::class)] Sequence $sequence
    ): void {
        $elements = $sequence->getElements() ?? [];
        $element = reset($elements);
        $this->neopixelService->writeLeds(
            $module,
            $this->ledMapper->mapFromArrays(
                $module,
                JsonUtility::decode($element->getData()),
                true,
                false
            )
        );
    }

    public function sendAnimation(
        #[Event\Parameter(ModuleParameter::class)] Module $module,
        int $animationId
    ): void {
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Zufallsanzeige')]
    public function randomImage(
        #[Event\Parameter(ModuleParameter::class)] Module $module,
        #[Event\Parameter(IntParameter::class, 'Start LED', ['range' => [1, LedMapper::MAX_PROTOCOL_LEDS + 1]])] int $start = 0,
        #[Event\Parameter(IntParameter::class, 'End LED', ['range' => [1, LedMapper::MAX_PROTOCOL_LEDS + 1]])] int $end = LedMapper::MAX_PROTOCOL_LEDS + 1,
        #[Event\Parameter(IntParameter::class, 'Rot von', ['range' => [0, 255]])] int $redFrom = 0,
        #[Event\Parameter(IntParameter::class, 'Rot bis', ['range' => [0, 255]])] int $redTo = 255,
        #[Event\Parameter(IntParameter::class, 'Grün von', ['range' => [0, 255]])] int $greenFrom = 0,
        #[Event\Parameter(IntParameter::class, 'Grün bis', ['range' => [0, 255]])] int $greenTo = 255,
        #[Event\Parameter(IntParameter::class, 'Blau von', ['range' => [0, 255]])] int $blueFrom = 0,
        #[Event\Parameter(IntParameter::class, 'Blau bis', ['range' => [0, 255]])] int $blueTo = 255,
        #[Event\Parameter(OptionParameter::class, 'Einblenden', ['options' => [[
            0 => 'Nicht',
            1 => 'Verdammt langsam',
            2 => 'Extrem langsam',
            3 => 'Sehr sehr langsam',
            4 => 'Sehr langsam',
            5 => 'Ganz langsam',
            6 => 'Langsamer',
            7 => 'Langsam',
            8 => 'Normal',
            9 => 'Schnell',
            10 => 'Schneller',
            11 => 'Ganz schnell',
            12 => 'Sehr schnell',
            13 => 'Sehr sehr schnell',
            14 => 'Extrem schnell',
            15 => 'Verdammt schnell',
        ]]])] int $fadeIn = 0
    ): void {
        $leds = [];

        for ($i = $start; $i <= $end; ++$i) {
            $red = mt_rand($redFrom, $redTo);
            $green = mt_rand($greenFrom, $greenTo);
            $blue = mt_rand($blueFrom, $blueTo);
            $this->logger->debug(sprintf('Set LED %d to %d,%d,%d', $i - 1, $red, $green, $blue));
            $leds[$i - 1] = (new Led(
                $module,
                $i - 1,
                red: $red,
                green: $green,
                blue: $blue,
                fadeIn: $fadeIn
            ))->setOnlyColor(true);
        }

        $this->neopixelService->writeLeds($module, $leds);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     * @throws JsonException
     */
    #[Event\Method('Farbe setzen')]
    public function sendColor(
        #[Event\Parameter(ModuleParameter::class)] Module $module,
        #[Event\Parameter(StringParameter::class, 'LEDs')] string $ledRanges,
        #[Event\Parameter(IntParameter::class, 'Rot', ['range' => [0, 255]])] int $red = 0,
        #[Event\Parameter(IntParameter::class, 'Grün', ['range' => [0, 255]])] int $green = 0,
        #[Event\Parameter(IntParameter::class, 'Blau', ['range' => [0, 255]])] int $blue = 0,
        #[Event\Parameter(OptionParameter::class, 'Einblenden', ['options' => [[
            0 => 'Nicht',
            1 => 'Verdammt langsam',
            2 => 'Extrem langsam',
            3 => 'Sehr sehr langsam',
            4 => 'Sehr langsam',
            5 => 'Ganz langsam',
            6 => 'Langsamer',
            7 => 'Langsam',
            8 => 'Normal',
            9 => 'Schnell',
            10 => 'Schneller',
            11 => 'Ganz schnell',
            12 => 'Sehr schnell',
            13 => 'Sehr sehr schnell',
            14 => 'Extrem schnell',
            15 => 'Verdammt schnell',
        ]]])] int $fadeIn = 0,
        #[Event\Parameter(IntParameter::class, 'Blinken', ['range' => [0, 31]])] int $blink = 0,
    ): void {
        $leds = [];

        foreach ($this->getLedNumbers($module, $ledRanges) as $ledNumber) {
            $this->logger->debug(sprintf('Set LED %d to %d,%d,%d', $ledNumber, $red, $green, $blue));
            $leds[$ledNumber] = (new Led(
                $module,
                $ledNumber,
                red: $red,
                green: $green,
                blue: $blue,
                fadeIn: $fadeIn,
                blink: $blink
            ))->setOnlyColor(true);
        }

        $this->neopixelService->writeLeds($module, $leds);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     * @throws ReflectionException
     * @throws JsonException
     */
    #[Event\Method('Heller')]
    public function brighter(
        #[Event\Parameter(ModuleParameter::class)] Module $module,
        #[Event\Parameter(StringParameter::class, 'LEDs')] string $ledRanges,
        #[Event\Parameter(IntParameter::class, 'Rot addieren', ['range' => [0, 255]])] int $red = 0,
        #[Event\Parameter(IntParameter::class, 'Grün addieren', ['range' => [0, 255]])] int $green = 0,
        #[Event\Parameter(IntParameter::class, 'Blau addieren', ['range' => [0, 255]])] int $blue = 0,
        #[Event\Parameter(OptionParameter::class, 'Einblenden', ['options' => [[
            0 => 'Nicht',
            1 => 'Verdammt langsam',
            2 => 'Extrem langsam',
            3 => 'Sehr sehr langsam',
            4 => 'Sehr langsam',
            5 => 'Ganz langsam',
            6 => 'Langsamer',
            7 => 'Langsam',
            8 => 'Normal',
            9 => 'Schnell',
            10 => 'Schneller',
            11 => 'Ganz schnell',
            12 => 'Sehr schnell',
            13 => 'Sehr sehr schnell',
            14 => 'Extrem schnell',
            15 => 'Verdammt schnell',
        ]]])] int $fadeIn = 0
    ): void {
        $leds = [];

        foreach ($this->getLedNumbers($module, $ledRanges) as $ledNumber) {
            $led = $this->attributeRepository->loadDto(new Led($module, $ledNumber));
            $ledRed = min($led->getRed() + $red, 255);
            $ledGreen = min($led->getGreen() + $green, 255);
            $ledBlue = min($led->getBlue() + $blue, 255);
            $this->logger->debug(sprintf('Set LED %d to %d,%d,%d', $ledNumber, $ledRed, $ledGreen, $ledBlue));
            $leds[$ledNumber] = $led
                ->setRed($ledRed)
                ->setGreen($ledGreen)
                ->setBlue($ledBlue)
                ->setFadeIn($fadeIn)
                ->setOnlyColor(true)
            ;
        }

        $this->neopixelService->writeLeds($module, $leds);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     * @throws ReflectionException
     */
    #[Event\Method('Dunkler')]
    public function darker(
        #[Event\Parameter(ModuleParameter::class)] Module $module,
        #[Event\Parameter(StringParameter::class, 'LEDs')] string $ledRanges,
        #[Event\Parameter(IntParameter::class, 'Rot subtrahieren', ['range' => [0, 255]])] int $red,
        #[Event\Parameter(IntParameter::class, 'Grün subtrahieren', ['range' => [0, 255]])] int $green,
        #[Event\Parameter(IntParameter::class, 'Blau subtrahieren', ['range' => [0, 255]])] int $blue,
        #[Event\Parameter(OptionParameter::class, 'Einblenden', ['options' => [[
            0 => 'Nicht',
            1 => 'Verdammt langsam',
            2 => 'Extrem langsam',
            3 => 'Sehr sehr langsam',
            4 => 'Sehr langsam',
            5 => 'Ganz langsam',
            6 => 'Langsamer',
            7 => 'Langsam',
            8 => 'Normal',
            9 => 'Schnell',
            10 => 'Schneller',
            11 => 'Ganz schnell',
            12 => 'Sehr schnell',
            13 => 'Sehr sehr schnell',
            14 => 'Extrem schnell',
            15 => 'Verdammt schnell',
        ]]])] int $fadeIn = 0
    ): void {
        $leds = [];

        foreach ($this->getLedNumbers($module, $ledRanges) as $ledNumber) {
            $led = $this->attributeRepository->loadDto(new Led($module, $ledNumber));
            $ledRed = max($led->getRed() - $red, 0);
            $ledGreen = max($led->getGreen() - $green, 0);
            $ledBlue = max($led->getBlue() - $blue, 0);
            $this->logger->debug(sprintf('Set LED %d to %d,%d,%d', $ledNumber, $ledRed, $ledGreen, $ledBlue));
            $leds[$ledNumber] = $led
                ->setRed($ledRed)
                ->setGreen($ledGreen)
                ->setBlue($ledBlue)
                ->setFadeIn($fadeIn)
                ->setOnlyColor(true)
            ;
        }

        $this->neopixelService->writeLeds($module, $leds);
    }

    /**
     * @throws JsonException
     *
     * @return int[]
     */
    private function getLedNumbers(Module $module, string $leds): array
    {
        $this->logger->debug(sprintf('Get LED Numbers from %s', $leds));

        if ($leds === '') {
            $config = JsonUtility::decode($module->getConfig() ?? '[]');

            return range(0, (int) (array_sum($config['counts']) - 1));
        }

        $ledRanges = explode(',', $leds);
        $numbers = [];

        foreach ($ledRanges as $ledRange) {
            $ledRange = explode('-', $ledRange);

            if (count($ledRange) === 1) {
                $ledRange[1] = $ledRange[0];
            }

            for ($i = (int) $ledRange[0]; $i <= (int) $ledRange[1]; ++$i) {
                $number = $i - 1;
                $numbers[$number] = $number;
            }
        }

        ksort($numbers);

        return array_values($numbers);
    }
}
