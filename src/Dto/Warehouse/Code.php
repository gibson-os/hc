<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Warehouse;

enum Code: string
{
    case EAN = 'EAN';
    case GTIN = 'GTIN';
    case ASIN = 'ASIN';
    case MPNR = 'MPNR';
    case SMD = 'SMD';
    case ISBN = 'ISBN';
}
