<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Enum\Blueprint;

enum Type: string
{
    case FRAME = 'frame';
    case ROOM = 'room';
    case FURNISHING = 'furnishing';
    case MODULE = 'module';
}
