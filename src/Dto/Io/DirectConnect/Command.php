<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Io\DirectConnect;

use GibsonOS\Module\Hc\Dto\Io\Port;

class Command
{
    public function __construct(
        private bool $inputPortValue,
        private Port $outputPort,
        private bool $hasMore
    ) {
    }
}
