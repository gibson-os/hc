<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Core\Service\TwigService;
use GibsonOS\Module\Hc\Dto\Formatter\Explain;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;
use GibsonOS\Module\Hc\Service\TransformService;
use LogicException;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\TwigFilter;

abstract class AbstractHcFormatter extends AbstractFormatter
{
    protected TwigService $twigService;

    public function __construct(TransformService $transformService, TwigService $twigService)
    {
        parent::__construct($transformService);
        $this->twigService = $twigService;

        try {
            $this->twigService->getTwig()->addFilter(new TwigFilter(
                'asciiToUnsignedInt',
                [$this->transformService, 'asciiToUnsignedInt']
            ));
        } catch (LogicException $e) {
            // do nothing
        }

        try {
            $this->twigService->getTwig()->addFilter(new TwigFilter(
                'transformHertz',
                [$this->transformService, 'transformHertz']
            ));
        } catch (LogicException $e) {
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

        return $this->renderBlock($command, 'command') ?? parent::command($log);
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

        return $this->renderBlock($command, 'text', ['data' => $log->getRawData()]) ??
            parent::text($log)
        ;
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

        switch ($command) {
            case AbstractHcSlave::COMMAND_ADDRESS:
            case AbstractHcSlave::COMMAND_BUFFER_SIZE:
            case AbstractHcSlave::COMMAND_EEPROM_ERASE:
            case AbstractHcSlave::COMMAND_TYPE:
                return [new Explain(0, 0, $this->renderBlock($command, 'explain', $context) ?? '')];
            case AbstractHcSlave::COMMAND_DEVICE_ID:
            case AbstractHcSlave::COMMAND_EEPROM_FREE:
            case AbstractHcSlave::COMMAND_EEPROM_POSITION:
            case AbstractHcSlave::COMMAND_EEPROM_SIZE:
                return [new Explain(0, 1, $this->renderBlock($command, 'explain', $context) ?? '')];
            case AbstractHcSlave::COMMAND_PWM_SPEED:
            case AbstractHcSlave::COMMAND_HERTZ:
            return [new Explain(0, 3, $this->renderBlock($command, 'explain', $context) ?? '')];
        }

        return parent::explain($log);
    }

    /**
     * @throws Throwable
     * @throws LoaderError
     * @throws SyntaxError
     */
    protected function renderBlock(int $command, string $blockName, array $context = []): ?string
    {
        try {
            $templates = $this->getTemplates();

            return isset($templates[$command])
                ? $this->twigService->getTwig()
                    ->load('@hc/formatter/' . $templates[$command] . '.html.twig')
                    ->renderBlock($blockName, $context)
                : null;
        } catch (RuntimeError $e) {
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
