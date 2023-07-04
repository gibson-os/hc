<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Module\Hc\Dto\Direction;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;

abstract class AbstractFormatter implements FormatterInterface
{
    public function __construct(protected TransformService $transformService)
    {
    }

    public function command(Log $log): ?string
    {
        return empty($log->getCommand()) ? null : (string) $log->getCommand();
    }

    public function render(Log $log): ?string
    {
        return null;
    }

    public function text(Log $log): ?string
    {
        if ($log->getType() == MasterService::TYPE_HANDSHAKE) {
            return sprintf(
                'Adresse %d gesendet an %s',
                $this->transformService->asciiToUnsignedInt($log->getRawData(), 0),
                substr($log->getRawData(), 1)
            );
        }
        if (
            $log->getType() == MasterService::TYPE_STATUS
            && $log->getDirection() === Direction::OUTPUT
        ) {
            return 'Status abfragen';
        }

        return null;
    }

    public function explain(Log $log): ?array
    {
        return null;
    }

    protected function isDefaultType(Log $log): bool
    {
        if ($log->getType() === MasterService::TYPE_HANDSHAKE) {
            return true;
        }

        if (
            $log->getType() === MasterService::TYPE_STATUS
            && $log->getDirection() === Direction::OUTPUT
        ) {
            return true;
        }

        return false;
    }
}
