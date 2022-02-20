<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Io;

enum Direction: int
{
    case INPUT = 0;
    case OUTPUT = 1;
}
