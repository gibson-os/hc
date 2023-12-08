<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Enum\Warehouse\Label;

enum Barcode: string
{
    case CODE39 = 'C39';
    case CODE39_CHECKSUM = 'C39+';
    case CODE39_EXTENDED = 'C39E';
    case CODE39_EXTENDED_CHECKSUM = 'C39E+';
    case CODE93 = 'C93';
    case STANDARD25 = 'S25';
    case STANDARD25_CHECKSUM = 'S25+';
    case INTERLEAVED25 = 'I25';
    case INTERLEAVED25_CHECKSUM = 'I25+';
    case CODE128 = 'C128';
    case CODE128A = 'C128A';
    case CODE128B = 'C128B';
    case CODE128C = 'C128C';
    case EAN2 = 'EAN2';
    case EAN5 = 'EAN5';
    case EAN8 = 'EAN8';
    case EAN13 = 'EAN13';
    case UPC_A = 'UPCA';
    case UPC_E = 'UPCE';
    case MSI = 'MSI';
    case MSI_CHECKSUM = 'MSI+';
    case POSTNET = 'POSTNET';
    case PLANET = 'PLANET';
    case ROYAL_MAIL = 'PMS4CC';
    case KLANT_INDEX = 'KIX';
    case INTELLIGENT_MAIL = 'IMB';
    case CODEABAR = 'CODEABAR';
    case CODE11 = 'CODE11';
    case PHARMA = 'PHARMA';
    case PHARMA_TWO_TRACKS = 'PHARMA2T';
    case DATAMATRIX = 'DATAMATRIX';
    case PDF417 = 'PDF417';
    case QRCODE = 'QRCODE';

    public function is1d(): bool
    {
        return !self::is2d();
    }

    public function is2d(): bool
    {
        return match ($this) {
            self::DATAMATRIX,
            self::PDF417,
            self::QRCODE => true,
            default => false,
        };
    }
}
