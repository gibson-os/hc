<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Core\Service\TwigService;
use GibsonOS\Module\Hc\Dto\Formatter\Explain;
use GibsonOS\Module\Hc\Exception\ModuleException;
use GibsonOS\Module\Hc\Mapper\LedMapper;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\Module\Hc\Store\Neopixel\LedStore;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

class NeopixelFormatter extends AbstractHcFormatter
{
    /**
     * @var array<int, array<int, Led>>
     */
    private array $leds = [];

    public function __construct(
        TransformService $transformService,
        TwigService $twigService,
        TypeRepository $typeRepository,
        private LedStore $ledStore,
        private LedMapper $ledMapper
    ) {
        parent::__construct($transformService, $twigService, $typeRepository);
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
        $data = $log->getRawData();

        switch ($command) {
            case NeopixelService::COMMAND_SEQUENCE_ADD_STEP:
                $command = NeopixelService::COMMAND_SET_LEDS;
                $data = substr($data, 2);
                // no break
            case NeopixelService::COMMAND_SET_LEDS:
                $module = $log->getModule();

                if ($module === null) {
                    throw new ModuleException(sprintf('No module set in log entry #%d', $log->getId() ?? 0));
                }

                $slaveLeds = $this->getLeds($module);

                $context = [
                    'slaveLeds' => $slaveLeds,
                    'logLeds' => $this->ledMapper->mapFromString($module, $data),
                    'maxTop' => (
                        empty($slaveLeds)
                            ? 0
                            : max(array_map(static fn (Led $slaveLed) => $slaveLed->getTop(), $slaveLeds))
                    ) + 6,
                ];

                break;
        }

        return $this->renderBlock($command, AbstractHcFormatter::BLOCK_RENDER, $context) ?? parent::render($log);
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

                return $this->renderBlock($command, AbstractHcFormatter::BLOCK_TEXT, $context);
        }

        return parent::text($log);
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
        $command = $log->getCommand();

        if ($command === null) {
            return parent::explain($log);
        }

        $explains = [];
        $data = $log->getRawData();

        switch ($command) {
            case NeopixelService::COMMAND_SEQUENCE_ADD_STEP:
                $module = $log->getModule();

                if ($module === null) {
                    return null;
                }

                return array_merge(
                    [new Explain(
                        0,
                        1,
                        $this->renderBlock(
                            $command,
                            AbstractHcFormatter::BLOCK_EXPLAIN,
                            [
                                'runtime' => str_replace(
                                    '.',
                                    ',',
                                    (string) ($this->transformService->asciiToUnsignedInt(substr($data, 0, 2))
                                    / ($module->getPwmSpeed() ?? 1))
                                ),
                            ]
                        ) ?? ''
                    )],
                    $this->explainSetLeds($data, 2)
                );
            case NeopixelService::COMMAND_SET_LEDS:
                return $this->explainSetLeds($data);
            case NeopixelService::COMMAND_LED_COUNTS:
            case NeopixelService::COMMAND_CHANNEL_WRITE:
                $channel = 1;

                for ($i = 0; $i < strlen($log->getRawData()); $i += 2) {
                    $explains[] = new Explain(
                        $i,
                        $i + 1,
                        $this->renderBlock(
                            $command,
                            AbstractHcFormatter::BLOCK_EXPLAIN,
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
    private function getLeds(Module $module): array
    {
        $moduleId = $module->getId() ?? 0;

        if (!isset($this->leds[$moduleId])) {
            $this->ledStore->setModule($module);
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
    private function getLedExplains(string $data, int &$i): array
    {
        return [
            (new Explain($i, $i, $this->renderBlock(NeopixelService::COMMAND_SET_LEDS, AbstractHcFormatter::BLOCK_EXPLAIN, [
                'part' => 'red',
                'red' => $this->transformService->asciiToUnsignedInt($data, $i++),
            ]) ?? ''))->setColor(Explain::COLOR_RED),
            (new Explain($i, $i, $this->renderBlock(NeopixelService::COMMAND_SET_LEDS, AbstractHcFormatter::BLOCK_EXPLAIN, [
                'part' => 'green',
                'green' => $this->transformService->asciiToUnsignedInt($data, $i++),
            ]) ?? ''))->setColor(Explain::COLOR_GREEN),
            (new Explain($i, $i, $this->renderBlock(NeopixelService::COMMAND_SET_LEDS, AbstractHcFormatter::BLOCK_EXPLAIN, [
                'part' => 'blue',
                'blue' => $this->transformService->asciiToUnsignedInt($data, $i++),
            ]) ?? ''))->setColor(Explain::COLOR_BLUE),
            (new Explain($i, $i, $this->renderBlock(NeopixelService::COMMAND_SET_LEDS, AbstractHcFormatter::BLOCK_EXPLAIN, [
                'part' => 'effect',
                'fadeIn' => $this->transformService->asciiToUnsignedInt($data, $i) >> 4,
                'blink' => ($this->transformService->asciiToUnsignedInt($data, $i++) & 15),
            ]) ?? ''))->setColor(Explain::COLOR_BLACK),
        ];
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Throwable
     */
    private function explainSetLeds(string $data, int $offset = 0): array
    {
        $explains = [];

        for ($i = $offset; $i < strlen($data);) {
            $address = $this->transformService->asciiToUnsignedInt(substr($data, $i, 2));
            $explains[] = new Explain(
                $i,
                $i + 1,
                $this->renderBlock(
                    NeopixelService::COMMAND_SET_LEDS,
                    AbstractHcFormatter::BLOCK_EXPLAIN,
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
                        NeopixelService::COMMAND_SET_LEDS,
                        AbstractHcFormatter::BLOCK_EXPLAIN,
                        ['part' => 'rangeAddress', 'from' => $startAddress]
                    ) ?? ''
                );
                $explains[] = new Explain(
                    $endByte + 1,
                    $i + 1,
                    $this->renderBlock(
                        NeopixelService::COMMAND_SET_LEDS,
                        AbstractHcFormatter::BLOCK_EXPLAIN,
                        ['part' => 'rangeAddress', 'to' => $endAddress]
                    ) ?? ''
                );
                $i += 2;
                $explains = array_merge($explains, $this->getLedExplains($data, $i));

                continue;
            }

            if ($address > LedMapper::MAX_PROTOCOL_LEDS) {
                for ($j = 0; $j < $address - LedMapper::MAX_PROTOCOL_LEDS; ++$j) {
                    $explains[] = new Explain(
                        $i,
                        $i + 1,
                        $this->renderBlock(
                            NeopixelService::COMMAND_SET_LEDS,
                            AbstractHcFormatter::BLOCK_EXPLAIN,
                            [
                                'part' => 'address',
                                'address' => $this->transformService->asciiToUnsignedInt(substr($data, $i, 2)),
                            ]
                        ) ?? ''
                    );
                    $i += 2;
                }

                $explains = array_merge($explains, $this->getLedExplains($data, $i));

                continue;
            }

            $explains = array_merge($explains, $this->getLedExplains($data, $i));
        }

        return $explains;
    }

    protected function getTemplates(): array
    {
        return parent::getTemplates() + [
            NeopixelService::COMMAND_SET_LEDS => 'neopixel/setLeds',
            NeopixelService::COMMAND_LED_COUNTS => 'neopixel/ledCounts',
            NeopixelService::COMMAND_CHANNEL_WRITE => 'neopixel/channelWrite',
            NeopixelService::COMMAND_SEQUENCE_START => 'neopixel/sequenceStart',
            NeopixelService::COMMAND_SEQUENCE_PAUSE => 'neopixel/sequencePause',
            NeopixelService::COMMAND_SEQUENCE_STOP => 'neopixel/sequenceStop',
            NeopixelService::COMMAND_SEQUENCE_EEPROM_ADDRESS => 'neopixel/sequenceEepromAddress',
            NeopixelService::COMMAND_SEQUENCE_NEW => 'neopixel/sequenceNew',
            NeopixelService::COMMAND_SEQUENCE_ADD_STEP => 'neopixel/sequenceAddStep',
        ];
    }
}
