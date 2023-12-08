<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Enum\Io;

enum AddOrSub: int
{
    case SET = 0;
    case ADD = 1;
    case SUB = -1;
}
