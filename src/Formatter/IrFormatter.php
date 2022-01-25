<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Core\Attribute\GetSetting;
use GibsonOS\Core\Model\Setting;
use GibsonOS\Core\Service\TwigService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\Formatter\Explain;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\IrService;
use GibsonOS\Module\Hc\Service\TransformService;

class IrFormatter extends AbstractHcFormatter
{
    private array $irProtocols;

    public function __construct(
        TransformService $transformService,
        TwigService $twigService,
        TypeRepository $typeRepository,
        #[GetSetting('irProtocols')] Setting $irProtocols
    ) {
        parent::__construct($transformService, $twigService, $typeRepository);

        $this->irProtocols = JsonUtility::decode($irProtocols->getValue());
    }

    public function command(Log $log): ?string
    {
        return match ($log->getCommand()) {
            IrService::COMMAND_SEND => 'IR Kommando gesendet',
            default => parent::command($log),
        };
    }

    public function text(Log $log): ?string
    {
        return match ($log->getCommand()) {
            IrService::COMMAND_SEND => 'Infrarotdaten gesendet',
            default => parent::command($log),
        };
    }

    public function explain(Log $log): ?array
    {
        return match ($log->getCommand()) {
            IrService::COMMAND_SEND => [
                (new Explain(0, 0, sprintf(
                    'Protokoll: %s',
                    $this->irProtocols[$this->transformService->asciiToUnsignedInt($log->getRawData(), 0)]
                )))->setColor(Explain::COLOR_GREEN),
                (new Explain(1, 2, sprintf(
                    'Adresse: %d',
                    ($this->transformService->asciiToUnsignedInt($log->getRawData(), 1) << 8) |
                    $this->transformService->asciiToUnsignedInt($log->getRawData(), 2)
                )))->setColor(Explain::COLOR_YELLOW),
                (new Explain(3, 5, sprintf(
                    'Kommando: %d',
                    ($this->transformService->asciiToUnsignedInt($log->getRawData(), 3) << 8) |
                    $this->transformService->asciiToUnsignedInt($log->getRawData(), 4)
                )))->setColor(Explain::COLOR_BLUE),
            ],
            default => parent::explain($log),
        };
    }
}
