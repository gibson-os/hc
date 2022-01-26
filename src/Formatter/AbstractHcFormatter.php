<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\TwigService;
use GibsonOS\Module\Hc\Dto\Formatter\Explain;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;
use GibsonOS\Module\Hc\Service\TransformService;
use LogicException;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\TemplateWrapper;
use Twig\TwigFilter;

abstract class AbstractHcFormatter extends AbstractFormatter
{
    protected const BLOCK_TEXT = 'text';

    protected const BLOCK_COMMAND = 'command';

    protected const BLOCK_RENDER = 'render';

    protected const BLOCK_EXPLAIN = 'explain';

    /**
     * @var TemplateWrapper[]
     */
    private array $loadedTemplates = [];

    /**
     * @var Type[]
     */
    private array $loadedTypes = [];

    public function __construct(
        TransformService $transformService,
        protected TwigService $twigService,
        protected TypeRepository $typeRepository
    ) {
        parent::__construct($transformService);

        try {
            $this->twigService->getTwig()->addFilter(new TwigFilter(
                'asciiToUnsignedInt',
                [$this->transformService, 'asciiToUnsignedInt']
            ));
        } catch (LogicException) {
            // do nothing
        }

        try {
            $this->twigService->getTwig()->addFilter(new TwigFilter(
                'transformHertz',
                [$this->transformService, 'transformHertz']
            ));
        } catch (LogicException) {
            // do nothing
        }

        try {
            $this->twigService->getTwig()->addFilter(new TwigFilter(
                'dechex',
                'dechex'
            ));
        } catch (LogicException) {
            // do nothing
        }
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
            return null;
        }

        return $this->renderBlock($command, self::BLOCK_COMMAND) ?? parent::command($log);
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

        $context = ['data' => $log->getRawData()];

        switch ($command) {
            case AbstractHcSlave::COMMAND_TYPE:
                $typeId = $this->transformService->asciiToUnsignedInt($log->getRawData());

                if (!isset($this->loadedTypes[$typeId])) {
                    try {
                        $this->loadedTypes[$typeId] = $this->typeRepository->getById($typeId);
                    } catch (SelectError) {
                        $this->loadedTypes[$typeId] = (new Type())
                            ->setId($typeId)
                            ->setName('Unbekannter Typ')
                        ;
                    }
                }

                $context['type'] = $this->loadedTypes[$typeId];

                break;
        }

        return $this->renderBlock($command, self::BLOCK_TEXT, $context) ?? parent::text($log);
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

        $context = ['data' => $log->getRawData()];

        return match ($command) {
            AbstractHcSlave::COMMAND_ADDRESS,
            AbstractHcSlave::COMMAND_BUFFER_SIZE,
            AbstractHcSlave::COMMAND_EEPROM_ERASE,
            AbstractHcSlave::COMMAND_TYPE => [
                new Explain(0, 0, $this->renderBlock($command, self::BLOCK_EXPLAIN, $context) ?? ''),
            ],
            AbstractHcSlave::COMMAND_DEVICE_ID,
            AbstractHcSlave::COMMAND_EEPROM_FREE,
            AbstractHcSlave::COMMAND_EEPROM_POSITION,
            AbstractHcSlave::COMMAND_EEPROM_SIZE => [
                new Explain(0, 1, $this->renderBlock($command, self::BLOCK_EXPLAIN, $context) ?? ''),
            ],
            AbstractHcSlave::COMMAND_PWM_SPEED,
            AbstractHcSlave::COMMAND_HERTZ => [
                new Explain(0, 3, $this->renderBlock($command, self::BLOCK_EXPLAIN, $context) ?? ''),
            ],
            default => parent::explain($log),
        };
    }

    /**
     * @throws Throwable
     * @throws LoaderError
     * @throws SyntaxError
     */
    protected function renderBlock(int $command, string $blockName, array $context = []): ?string
    {
        $templates = $this->getTemplates();

        if (!isset($templates[$command])) {
            return null;
        }

        $template = $templates[$command];

        try {
            if (!isset($this->loadedTemplates[$template])) {
                $this->loadedTemplates[$template] = $this->twigService->getTwig()
                    ->load('@hc/formatter/' . $template . '.html.twig');
            }

            return $this->loadedTemplates[$template]->renderBlock($blockName, $context);
        } catch (RuntimeError) {
            return null;
        }
    }

    /**
     * @return string[]
     */
    protected function getTemplates(): array
    {
        return [
            AbstractHcSlave::COMMAND_ADDRESS => 'abstractHc/address',
            AbstractHcSlave::COMMAND_ALL_LEDS => 'abstractHc/allLeds',
            AbstractHcSlave::COMMAND_BUFFER_SIZE => 'abstractHc/bufferSize',
            AbstractHcSlave::COMMAND_CONNECT_LED => 'abstractHc/connectLed',
            AbstractHcSlave::COMMAND_CUSTOM_LED => 'abstractHc/customLed',
            AbstractHcSlave::COMMAND_DEVICE_ID => 'abstractHc/deviceId',
            AbstractHcSlave::COMMAND_EEPROM_ERASE => 'abstractHc/eepromErase',
            AbstractHcSlave::COMMAND_EEPROM_FREE => 'abstractHc/eepromFree',
            AbstractHcSlave::COMMAND_EEPROM_POSITION => 'abstractHc/eepromPosition',
            AbstractHcSlave::COMMAND_EEPROM_SIZE => 'abstractHc/eepromSize',
            AbstractHcSlave::COMMAND_ERROR_LED => 'abstractHc/errorLed',
            AbstractHcSlave::COMMAND_HERTZ => 'abstractHc/hertz',
            AbstractHcSlave::COMMAND_LEDS => 'abstractHc/leds',
            AbstractHcSlave::COMMAND_POWER_LED => 'abstractHc/powerLed',
            AbstractHcSlave::COMMAND_PWM_SPEED => 'abstractHc/pwmSpeed',
            AbstractHcSlave::COMMAND_RECEIVE_LED => 'abstractHc/receiveLed',
            AbstractHcSlave::COMMAND_RESTART => 'abstractHc/restart',
            AbstractHcSlave::COMMAND_RGB_LED => 'abstractHc/rgbLed',
            AbstractHcSlave::COMMAND_STATUS => 'abstractHc/status',
            AbstractHcSlave::COMMAND_DATA_CHANGED => 'abstractHc/status',
            AbstractHcSlave::COMMAND_TRANSCEIVE_LED => 'abstractHc/tansceiveLed',
            AbstractHcSlave::COMMAND_TYPE => 'abstractHc/type',
        ];
    }
}
