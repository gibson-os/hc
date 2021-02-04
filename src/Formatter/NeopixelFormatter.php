<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Core\Service\TwigService;
use GibsonOS\Module\Hc\Dto\Formatter\Explain;
use GibsonOS\Module\Hc\Dto\Neopixel\Led;
use GibsonOS\Module\Hc\Mapper\LedMapper;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\Module\Hc\Store\Neopixel\LedStore;

class NeopixelFormatter extends AbstractHcFormatter
{
    private LedStore $ledStore;

    private LedMapper $ledMapper;

    /**
     * @var array<int, array<int, Led>>
     */
    private array $leds = [];

    private TwigService $twigService;

    public function __construct(
        TransformService $transformService,
        LedStore $ledStore,
        LedMapper $ledMapper,
        TwigService $twigService
    ) {
        parent::__construct($transformService);
        $this->ledStore = $ledStore;
        $this->ledMapper = $ledMapper;
        $this->twigService = $twigService;
    }

    public function command(Log $log): ?string
    {
        switch ($log->getCommand()) {
            case NeopixelService::COMMAND_SET_LEDS:
                return 'LEDs setzen';
            case NeopixelService::COMMAND_LED_COUNTS:
                return 'LED Anzahl setzen';
            case NeopixelService::COMMAND_CHANNEL_WRITE:
                return 'LEDs anzeigen';
            case NeopixelService::COMMAND_SEQUENCE_START:
                return 'Animation starten';
            case NeopixelService::COMMAND_SEQUENCE_PAUSE:
                return 'Animation pausieren';
            case NeopixelService::COMMAND_SEQUENCE_STOP:
                return 'Animation stoppen';
            case NeopixelService::COMMAND_SEQUENCE_EEPROM_ADDRESS:
                return 'Animation EEPROM Adresse';
            case NeopixelService::COMMAND_SEQUENCE_NEW:
                return 'Neue Animation';
            case NeopixelService::COMMAND_SEQUENCE_ADD_STEP:
                return 'Animations Schritt hinzufügen';
        }

        return parent::command($log);
    }

    public function render(Log $log): ?string
    {
        if ($log->getCommand() === NeopixelService::COMMAND_SET_LEDS) {
            $moduleLeds = $this->getLeds($log->getModuleId() ?? 0);
            $logLeds = $this->ledMapper->mapFromString($log->getRawData());
            $rendered = '';
            $maxTop = 0;

            foreach ($moduleLeds as $number => $moduleLed) {
                if ($moduleLed->getTop() > $maxTop) {
                    $maxTop = $moduleLed->getTop();
                }

                $rendered .=
                    '<div style="' .
                        'position: absolute;' .
                        'width: 3px;' .
                        'height: 3px;' .
                        'top: ' . $moduleLed->getTop() . 'px;' .
                        'left: ' . $moduleLed->getLeft() . 'px;' .
                        (isset($logLeds[$number])
                            ? 'background-color: rgb(' .
                                $logLeds[$number]->getRed() . ', ' .
                                $logLeds[$number]->getGreen() . ', ' .
                                $logLeds[$number]->getBlue() . ');'
                            : 'opacity: .5; background-color: #000;') .
                    '"></div>'
                ;
            }

            return '<div style="position: relative; height: ' . ($maxTop + 6) . 'px;">' . $rendered . '</div>';
        }

        return parent::render($log);
    }

    public function text(Log $log): ?string
    {
        if ($log->getCommand() === NeopixelService::COMMAND_CHANNEL_WRITE) {
            $texts = [];
            $channel = 1;

            for ($i = 0; $i < strlen($log->getRawData()); $i += 2) {
                $texts[] =
                    'Channel ' . $channel++ .
                    ' bis LED ' . $this->transformService->asciiToUnsignedInt(substr($log->getRawData(), $i, 2)) .
                    ' gesetzt.'
                ;
            }

            return implode('<br>', $texts);
        }

        return parent::text($log);
    }

    /**
     * @return Explain[]|null
     */
    public function explain(Log $log): ?array
    {
        if ($log->getCommand() === NeopixelService::COMMAND_SET_LEDS) {
            $explains = [];
            $data = $log->getRawData();

            for ($i = 0; $i < strlen($data);) {
                $address = $this->transformService->asciiToUnsignedInt(substr($data, $i, 2));
                $explains[] = new Explain(
                    $i,
                    $i + 1,
                    $address === LedMapper::RANGE_ADDRESS ? 'Adressbereich' :
                        ($address > LedMapper::MAX_PROTOCOL_LEDS ? 'Adressgruppe' : 'Adresse ' . $address)
                );
                $i += 2;

                if ($address === LedMapper::RANGE_ADDRESS) {
                    $startByte = $i;
                    $endByte = $i + 1;
                    $startAddress = $this->transformService->asciiToUnsignedInt(substr($data, $i, 2));
                    $i += 2;
                    $endAddress = $this->transformService->asciiToUnsignedInt(substr($data, $i, 2));
                    $i += 2;
                    $explains[] = new Explain(
                        $startByte,
                        $endByte,
                        'Adressbereich von ' . $startAddress . ' bis ' . $endAddress
                    );
                    $explains[] = $this->getLedExplain($data, $i);

                    continue;
                }

                if ($address > LedMapper::MAX_PROTOCOL_LEDS) {
                    for ($j = 0; $j < $address - LedMapper::MAX_PROTOCOL_LEDS; ++$j) {
                        $explains[] = new Explain(
                            $i,
                            $i + 1,
                            'Adresse ' . $this->transformService->asciiToUnsignedInt(substr($data, $i, 2))
                        );
                        $i += 2;
                    }

                    $explains[] = $this->getLedExplain($data, $i);

                    continue;
                }

                $explains[] = $this->getLedExplain($data, $i);
            }

            return $explains;
        }

        return parent::explain($log);
    }

    /**
     * @return Led[]
     */
    private function getLeds(int $moduleId): array
    {
        if (!isset($this->leds[$moduleId])) {
            $this->ledStore->setModule($moduleId);
            $this->leds[$moduleId] = $this->ledStore->getList();
        }

        return $this->leds[$moduleId];
    }

    private function getLedExplain(string $data, int &$i): Explain
    {
        $startByte = $i;
        $i += 4;

        return new Explain(
            $startByte,
            $i,
            'Rot: ' . $this->transformService->asciiToUnsignedInt($data, $i) . PHP_EOL .
            'Grün: ' . $this->transformService->asciiToUnsignedInt($data, $i - 3) . PHP_EOL .
            'Blau: ' . $this->transformService->asciiToUnsignedInt($data, $i - 2) . PHP_EOL .
            'Einblenden: ' . ($this->transformService->asciiToUnsignedInt($data, $i - 1) >> 4) . PHP_EOL .
            'Blinken: ' . ($this->transformService->asciiToUnsignedInt($data, $i - 1) & 15)
        );
    }
}
