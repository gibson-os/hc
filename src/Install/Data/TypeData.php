<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Install\Data;

use Generator;
use GibsonOS\Core\Dto\Install\Success;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Install\AbstractInstall;
use GibsonOS\Core\Service\InstallService;
use GibsonOS\Core\Service\PriorityInterface;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Type;
use JsonException;

class TypeData extends AbstractInstall implements PriorityInterface
{
    /**
     * @throws JsonException
     * @throws SaveError
     */
    public function install(string $module): Generator
    {
        $this
            ->setType(0, 'Neues Modul', 'blank', uiSettings: ['icon' => 'icon_bug'])
            ->setType(255, 'Neues Modul', 'blank', uiSettings: ['icon' => 'icon_bug'])
            ->setType(1, 'Bell', 'rfmbell', 12000000, false, uiSettings: ['icon' => 'icon_bell'])
            ->setType(2, 'RGB Panel 5x5', 'rfmrgbpanel5x5', 16000000, false, uiSettings: ['icon' => 'icon_led', 'width' => 520, 'height' => 610])
            ->setType(3, 'Bridge', 'ethbridge', 16000000, false, uiSettings: ['icon' => 'icon_bridge', 'width' => 300, 'height' => 610])
            ->setType(4, 'Rhinetower', 'rfmrhinetower', isHcSlave: false, uiSettings: ['icon' => 'icon_rhinetower', 'width' => 600, 'height' => 500])
            ->setType(5, 'Box Vario 33', 'rfmboxvario33', isHcSlave: false, uiSettings: ['icon' => 'icon_boxvario33', 'width' => 450, 'height' => 430])
            ->setType(6, 'Neopixel', 'neopixel', uiSettings: ['icon' => 'icon_led'])
            ->setType(8, 'I/O', 'io', hasInput: true)
            ->setType(266, 'BME 280', 'bme280', isHcSlave: false)
            ->setType(267, 'SSD1306', 'ssd1306', isHcSlave: false)
        ;

        yield new Success('Homecontrol types installed!');
    }

    /**
     * @throws SaveError
     * @throws JsonException
     */
    private function setType(
        int $id,
        string $name,
        string $helper,
        int $hertz = 0,
        bool $isHcSlave = true,
        bool $hasInput = false,
        array $uiSettings = null
    ): TypeData {
        $this->logger->info(sprintf('Add homecontrol type #%d "%s"', $id, $name));
        (new Type())
            ->setId($id)
            ->setName($name)
            ->setHelper($helper)
            ->setHertz($hertz)
            ->setIsHcSlave($isHcSlave)
            ->setHasInput($hasInput)
            ->setUiSettings($uiSettings === null ? null : JsonUtility::encode($uiSettings))
            ->save()
        ;

        return $this;
    }

    public function getPart(): string
    {
        return InstallService::PART_DATA;
    }

    public function getPriority(): int
    {
        return 0;
    }
}
