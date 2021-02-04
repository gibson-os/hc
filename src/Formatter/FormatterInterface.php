<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Module\Hc\Dto\Formatter\Explain;
use GibsonOS\Module\Hc\Model\Log;

interface FormatterInterface
{
    public function render(Log $log): ?string;

    public function text(Log $log): ?string;

    public function command(Log $log): ?string;

    /**
     * @return Explain[]|null
     */
    public function explain(Log $log): ?array;
}
