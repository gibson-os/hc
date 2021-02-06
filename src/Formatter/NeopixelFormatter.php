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
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class NeopixelFormatter extends AbstractHcFormatter
{
    private LedStore $ledStore;

    private LedMapper $ledMapper;

    /**
     * @var array<int, array<int, Led>>
     */
    private array $leds = [];

    private TwigService $twigService;

    private const TEMPLATES = [
        NeopixelService::COMMAND_SET_LEDS => 'setLeds',
        NeopixelService::COMMAND_LED_COUNTS => 'ledCounts',
        NeopixelService::COMMAND_CHANNEL_WRITE => 'channelWrite',
        NeopixelService::COMMAND_SEQUENCE_START => 'sequenceStart',
        NeopixelService::COMMAND_SEQUENCE_PAUSE => 'sequencePause',
        NeopixelService::COMMAND_SEQUENCE_STOP => 'sequenceStop',
        NeopixelService::COMMAND_SEQUENCE_EEPROM_ADDRESS => 'sequenceEepromAddress',
        NeopixelService::COMMAND_SEQUENCE_NEW => 'sequenceNew',
        NeopixelService::COMMAND_SEQUENCE_ADD_STEP => 'sequenceAddStep',
    ];

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

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Throwable
     */
    public function command(Log $log): ?string
    {
        $command = $log->getCommand();

        if ($command === null) {
            return parent::command($log);
        }

        return $this->renderBlock($command, 'command') ?? parent::command($log);
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Throwable
     */
    public function render(Log $log): ?string
    {
        $command = $log->getCommand();

        if ($command === null) {
            return parent::render($log);
        }

        $context = [];

        if ($command === NeopixelService::COMMAND_SET_LEDS) {
            $slaveLeds = $this->getLeds($log->getModuleId() ?? 0);
            $context = [
                'slaveLeds' => $slaveLeds,
                'logLeds' => $this->ledMapper->mapFromString($log->getRawData()),
                'maxTop' => (
                    empty($slaveLeds)
                    ? 0
                    : max(array_map(static fn (Led $slaveLed) => $slaveLed->getTop(), $slaveLeds))
                ) + 6,
            ];
        }

        return $this->renderBlock($command, 'render', $context) ?? parent::render($log);
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Throwable
     */
    public function text(Log $log): ?string
    {
        $command = $log->getCommand();

        if ($command === null) {
            return parent::text($log);
        }

        $context = [];

        switch ($command) {
            case NeopixelService::COMMAND_LED_COUNTS:
            case NeopixelService::COMMAND_CHANNEL_WRITE:
                $channels = [];
                $channel = 1;

                for ($i = 0; $i < strlen($log->getRawData()); $i += 2) {
                    $channels[$channel++] =
                        $this->transformService->asciiToUnsignedInt(substr($log->getRawData(), $i, 2));
                }

                $context['channels'] = $channels;

                break;
        }

        return $this->renderBlock($command, 'text', $context) ?? parent::text($log);
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Throwable
     *
     * @return Explain[]|null
     */
    public function explain(Log $log): ?array
    {
        $explains = [];
        $data = $log->getRawData();
        $command = $log->getCommand();

        if ($command === null) {
            return parent::explain($log);
        }

        switch ($command) {
            case NeopixelService::COMMAND_SET_LEDS:
                return $this->explainSetLeds($data, $command);
            case NeopixelService::COMMAND_LED_COUNTS:
            case NeopixelService::COMMAND_CHANNEL_WRITE:
                $channel = 1;

                for ($i = 0; $i < strlen($log->getRawData()); $i += 2) {
                    $explains[] = new Explain(
                        $i,
                        $i + 1,
                        $this->renderBlock(
                            $command,
                            'explain',
                            [
                                'channel' => $channel++,
                                'ledLength' => $this->transformService->asciiToUnsignedInt(substr($log->getRawData(), $i, 2)),
                            ]
                        ) ?? ''
                    );
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
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Throwable
     *
     * @return Explain[]
     */
    private function getLedExplains(string $data, int &$i, int $command): array
    {
        return [
            (new Explain($i, $i, $this->renderBlock($command, 'explain', [
                'part' => 'red',
                'red' => $this->transformService->asciiToUnsignedInt($data, $i++),
            ]) ?? ''))->setColor(Explain::COLOR_RED),
            (new Explain($i, $i, $this->renderBlock($command, 'explain', [
                'part' => 'green',
                'green' => $this->transformService->asciiToUnsignedInt($data, $i++),
            ]) ?? ''))->setColor(Explain::COLOR_GREEN),
            (new Explain($i, $i, $this->renderBlock($command, 'explain', [
                'part' => 'blue',
                'blue' => $this->transformService->asciiToUnsignedInt($data, $i++),
            ]) ?? ''))->setColor(Explain::COLOR_BLUE),
            (new Explain($i, $i, $this->renderBlock($command, 'explain', [
                'part' => 'effect',
                'fadeIn' => $this->transformService->asciiToUnsignedInt($data, $i) >> 4,
                'blink' => ($this->transformService->asciiToUnsignedInt($data, $i++) & 15),
            ]) ?? ''))->setColor(Explain::COLOR_BLACK),
        ];
    }

    /**
     * @throws Throwable
     * @throws LoaderError
     * @throws SyntaxError
     */
    private function renderBlock(int $command, string $blockName, array $context = []): ?string
    {
        try {
            return isset(self::TEMPLATES[$command])
                ? $this->twigService->getTwig()
                    ->load('@hc/formatter/neopixel/' . self::TEMPLATES[$command] . '.html.twig')
                    ->renderBlock($blockName, $context)
                : null;
        } catch (RuntimeError $e) {
            return null;
        }
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Throwable
     */
    private function explainSetLeds(string $data, int $command): array
    {
        $explains = [];

        for ($i = 0; $i < strlen($data);) {
            $address = $this->transformService->asciiToUnsignedInt(substr($data, $i, 2));
            $explains[] = new Explain(
                $i,
                $i + 1,
                $this->renderBlock(
                    $command,
                    'explain',
                    ['part' => 'address', 'address' => $address]
                ) ?? ''
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
                    $this->renderBlock(
                        $command,
                        'explain',
                        ['part' => 'rangeAddress', 'from' => $startAddress]
                    ) ?? ''
                );
                $explains[] = new Explain(
                    $endByte + 1,
                    $i + 1,
                    $this->renderBlock(
                        $command,
                        'explain',
                        ['part' => 'rangeAddress', 'to' => $endAddress]
                    ) ?? ''
                );
                $i += 2;
                $explains = array_merge($explains, $this->getLedExplains($data, $i, $command));

                continue;
            }

            if ($address > LedMapper::MAX_PROTOCOL_LEDS) {
                for ($j = 0; $j < $address - LedMapper::MAX_PROTOCOL_LEDS; ++$j) {
                    $explains[] = new Explain(
                        $i,
                        $i + 1,
                        $this->renderBlock(
                            $command,
                            'explain',
                            [
                                'part' => 'address',
                                'address' => $this->transformService->asciiToUnsignedInt(substr($data, $i, 2)),
                            ]
                        ) ?? ''
                    );
                    $i += 2;
                }

                $explains = array_merge($explains, $this->getLedExplains($data, $i, $command));

                continue;
            }

            $explains = array_merge($explains, $this->getLedExplains($data, $i, $command));
        }

        return $explains;
    }
}
