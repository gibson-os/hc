<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Core\Service\TwigService;
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
        TransformService $transform,
        LedStore $ledStore,
        LedMapper $ledMapper,
        TwigService $twigService
    ) {
        parent::__construct($transform);
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
}
