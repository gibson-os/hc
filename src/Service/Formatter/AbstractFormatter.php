<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Formatter;

use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;

abstract class AbstractFormatter implements FormatterInterface
{
    protected TransformService $transform;

    public function __construct(TransformService $transform)
    {
        $this->transform = $transform;
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
            return 'Adresse ' .
                $this->transform->hexToInt(substr($log->getData(), 0, 2)) .
                ' gesendet an ' .
                $this->transform->hexToAscii(substr($log->getData(), 2));
        }
        if (
            $log->getType() == MasterService::TYPE_STATUS &&
            $log->getDirection() === Log::DIRECTION_OUTPUT
        ) {
            return 'Status abfragen';
        }

        return null;
    }

    protected function isDefaultType(Log $log): bool
    {
        if ($log->getType() === MasterService::TYPE_HANDSHAKE) {
            return true;
        }

        if (
            $log->getType() === MasterService::TYPE_STATUS &&
            $log->getDirection() === Log::DIRECTION_OUTPUT
        ) {
            return true;
        }

        return false;
    }
}
