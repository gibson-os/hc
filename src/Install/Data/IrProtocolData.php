<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Install\Data;

use Generator;
use GibsonOS\Core\Dto\Install\Success;
use GibsonOS\Core\Install\AbstractInstall;
use GibsonOS\Core\Service\InstallService;
use GibsonOS\Core\Service\PriorityInterface;
use GibsonOS\Core\Utility\JsonUtility;

class IrProtocolData extends AbstractInstall implements PriorityInterface
{
    public function install(string $module): Generator
    {
        $this->setSetting(
            'hc',
            'ethbridgeIrProtocols',
            JsonUtility::encode([
                1 => 'Sony', 2 => 'NEC', 3 => 'Samsung', 4 => 'Matsushita', 5 => 'Kaseikyo',
                6 => 'Philips', 7 => 'RC5', 8 => 'Denon', 9 => 'RC6', 10 => 'Samsung32',
                11 => 'Apple', 12 => 'Philips', 13 => 'Nubert', 14 => 'Bang & Olufsen',
                15 => 'Grundig', 16 => 'Nokia', 17 => 'Siemens', 18 => 'FDC', 19 => 'RC Car',
                20 => 'JVC', 21 => 'RC6A', 22 => 'Nikon', 23 => 'Ruwido', 24 => 'IR60', 25 => 'Kathrein',
                26 => 'Netbox', 27 => 'NEC 16bit', 28 => 'NEC 42bit', 29 => 'LEGO', 30 => 'Thomson',
                31 => 'BOSE', 32 => 'A1',
            ])
        );

        yield new Success('IR protocols installed!');
    }

    public function getPart(): string
    {
        return InstallService::PART_DATA;
    }

    public function getModule(): ?string
    {
        return 'hc';
    }

    public function getPriority(): int
    {
        return 0;
    }
}
