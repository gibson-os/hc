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
use Throwable;
use Twig\TemplateWrapper;

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
            $slaveLeds = $this->getLeds($log->getModuleId() ?? 0);
            $maxTop = 0;
            $context = [
                'log' => $log,
                'slaveLeds' => $slaveLeds,
                'logLeds' => $this->ledMapper->mapFromString($log->getRawData()),
            ];

            foreach ($slaveLeds as $moduleLed) {
                if ($moduleLed->getTop() > $maxTop) {
                    $maxTop = $moduleLed->getTop();
                }
            }

            $context['maxTop'] = $maxTop + 6;
            $template = $this->twigService->getTwig()->load('@hc/formatter/neopixel/setLeds.html.twig');

            return $template->renderBlock('render', $context);
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
            $template = $this->twigService->getTwig()->load('@hc/formatter/neopixel/setLeds.html.twig');
            $explains = [];
            $data = $log->getRawData();

            for ($i = 0; $i < strlen($data);) {
                $address = $this->transformService->asciiToUnsignedInt(substr($data, $i, 2));
                $explains[] = new Explain(
                    $i,
                    $i + 1,
                    $template->renderBlock('explain', ['part' => 'address', 'address' => $address])
                );
                $i += 2;

                if ($address === LedMapper::RANGE_ADDRESS) {
                    $startByte = $i;
                    $endByte = $i + 1;
                    $startAddress = $this->transformService->asciiToUnsignedInt(substr($data, $i, 2));
                    $i += 2;
                    $endAddress = $this->transformService->asciiToUnsignedInt(substr($data, $i, 2));
                    $explains[] = new Explain(
                        $startByte,
                        $endByte,
                        $template->renderBlock('explain', ['part' => 'rangeAddress', 'from' => $startAddress])
                    );
                    $explains[] = new Explain(
                        $endByte + 1,
                        $i + 1,
                        $template->renderBlock('explain', ['part' => 'rangeAddress', 'to' => $endAddress])
                    );
                    $i += 2;
                    $explains = array_merge($explains, $this->getLedExplains($data, $i, $template));

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

                    $explains = array_merge($explains, $this->getLedExplains($data, $i, $template));

                    continue;
                }

                $explains = array_merge($explains, $this->getLedExplains($data, $i, $template));
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

    /**
     * @throws Throwable
     *
     * @return Explain[]
     */
    private function getLedExplains(string $data, int &$i, TemplateWrapper $template): array
    {
        return [
            (new Explain($i, $i, $template->renderBlock('explain', [
                'part' => 'red',
                'red' => $this->transformService->asciiToUnsignedInt($data, $i++),
            ])))->setColor(Explain::COLOR_RED),
            (new Explain($i, $i, $template->renderBlock('explain', [
                'part' => 'green',
                'green' => $this->transformService->asciiToUnsignedInt($data, $i++),
            ])))->setColor(Explain::COLOR_GREEN),
            (new Explain($i, $i, $template->renderBlock('explain', [
                'part' => 'blue',
                'blue' => $this->transformService->asciiToUnsignedInt($data, $i++),
            ])))->setColor(Explain::COLOR_BLUE),
            (new Explain($i, $i, $template->renderBlock('explain', [
                'part' => 'effect',
                'fadeIn' => $this->transformService->asciiToUnsignedInt($data, $i) >> 4,
                'blink' => ($this->transformService->asciiToUnsignedInt($data, $i++) & 15),
            ])))->setColor(Explain::COLOR_BLACK),
        ];
    }
}
