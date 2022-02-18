<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto;

enum Direction: string
{
    case Input = 'input';
    case Output = 'output';
}
