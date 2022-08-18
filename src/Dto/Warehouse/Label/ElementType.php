<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Warehouse\Label;

enum ElementType: string
{
    case UUID = 'uuid';
    case NAME = 'name';
    case DESCRIPTION = 'description';
    case STOCK = 'stock';
    case IMAGE = 'image';
    case CODE = 'code';
    case TAG = 'tag';
    case LINK = 'link';
}