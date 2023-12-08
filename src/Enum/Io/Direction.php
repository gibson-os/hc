<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Enum\Io;

enum Direction: int
{
    case INPUT = 0;
    case OUTPUT = 1;
}
