<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Warehouse\Label\Element;

enum Type: string
{
    case UUID = 'UUID';
    case NAME = 'Name';
    case DESCRIPTION = 'Beschreibung';
    case STOCK = 'Anzahl';
    case IMAGE = 'Bilder';
    case CODE = 'Codes';
    case TAG = 'Tags';
    case LINK = 'Links';
}
