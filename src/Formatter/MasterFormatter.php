<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\TransformService;
use Psr\Log\LoggerInterface;

class MasterFormatter implements FormatterInterface
{
    public function __construct(private TransformService $transformService, private LoggerInterface $logger)
    {
    }

    public function render(Log $log): ?string
    {
        return null;
    }

    public function text(Log $log): ?string
    {
        return null;
    }

    public function command(Log $log): ?string
    {
        return null;
    }

    public function explain(Log $log): ?array
    {
        return null;
    }
}
