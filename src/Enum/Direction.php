<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Enum;

enum Direction: string
{
    case INPUT = 'input';
    case OUTPUT = 'output';
}
