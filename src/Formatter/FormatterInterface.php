<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Module\Hc\Model\Log;

interface FormatterInterface
{
    public function render(Log $log): ?string;

    public function text(Log $log): ?string;

    public function command(Log $log): ?string;
}
