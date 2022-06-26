<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Warehouse;

enum Code: string
{
    case EAN = 'ean';
    case GTIN = 'gtin';
    case ASIN = 'asin';
    case MPNR = 'mpnr';
    case SMD = 'smd';
    case ISBN = 'isbn';
}
