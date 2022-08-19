<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Warehouse\Label\Element;

enum Type: string
{
    case UUID = 'UUID';
    case NAME = 'Name';
    case DESCRIPTION = 'Beschreibung';
    case STOCK = 'Anzahl';
    case IMAGE = 'Bild';
    case CODES = 'Codes';
    case TAGS = 'Tags';
    case LINKS = 'Links';
}
