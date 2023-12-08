<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Enum\Ir;

enum Protocol: int
{
    case SONY = 1;
    case NEC = 2;
    case SAMSUNG = 3;
    case MATSUSHITA = 4;
    case KASEIKYO = 5;
    case PHILIPS = 6;
    case RC5 = 7;
    case DENON = 8;
    case RC6 = 9;
    case SAMSUNG32 = 10;
    case APPLE = 11;
    case PHILIPS2 = 12;
    case NUBERT = 13;
    case BANG_OLUFSEN = 14;
    case GRUNDIG = 15;
    case NOKIA = 16;
    case SIEMENS = 17;
    case JVC = 20;
    case RC6A = 21;
    case NIKON = 22;
    case RUWIDO = 23;
    case IR60 = 24;
    case NEC16 = 27;
    case NEC42 = 28;
    case THOMSON = 30;
    case BOSE = 31;
    case A1 = 32;
    case TELEFUNKEN = 34;
    case SPEAKER = 39;
    case LG = 40;
    case PENTAX = 43;
    case MITSUBISHI = 49;
    case IRMP = 52;
    case MELINERA = 60;

    public function getName(): string
    {
        return match ($this) {
            self::SONY => 'Sony',
            self::NEC => 'NEC',
            self::SAMSUNG => 'Samsung',
            self::MATSUSHITA => 'Matsushita',
            self::KASEIKYO => 'Kaseiko',
            self::PHILIPS => 'Philips',
            self::RC5 => 'RC5',
            self::DENON => 'Denon',
            self::RC6 => 'RC6',
            self::SAMSUNG32 => 'Samsung62',
            self::APPLE => 'Apple',
            self::PHILIPS2 => 'Philips',
            self::NUBERT => 'Nubert',
            self::BANG_OLUFSEN => 'Bang & Olufsen',
            self::GRUNDIG => 'Grundig',
            self::NOKIA => 'Nokia',
            self::SIEMENS => 'Siemens',
            self::JVC => 'JVC',
            self::RC6A => 'RC6A',
            self::NIKON => 'Nikon',
            self::RUWIDO => 'Ruwido',
            self::IR60 => 'IR60',
            self::NEC16 => 'NEC 16bit',
            self::NEC42 => 'NEC 42bit',
            self::THOMSON => 'Thomson',
            self::BOSE => 'Bose',
            self::A1 => 'A1',
            self::TELEFUNKEN => 'TELEFUNKEN',
            self::SPEAKER => 'Speaker',
            self::LG => 'LG',
            self::PENTAX => 'Pentax',
            self::MITSUBISHI => 'Mitsubishi',
            self::IRMP => 'IRMP',
            self::MELINERA => 'Melinera',
        };
    }
}
