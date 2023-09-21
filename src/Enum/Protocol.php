<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Enum;

enum Protocol: string
{
    case UDP = 'udp';
    case RFM = 'rfm';
    case HTTP = 'http';
}
