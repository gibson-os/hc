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
            ->setType(0, 'Neues Modul', 'blank', uiSettings: ['icon' => 'icon_bug'], defaultAddresses: [97])
            ->setType(255, 'Neues Modul', 'blank', uiSettings: ['icon' => 'icon_bug'], defaultAddresses: [97])
            ->setType(4, 'Rhinetower', 'rfmrhinetower', isHcSlave: false, uiSettings: ['icon' => 'icon_rhinetower', 'width' => 600, 'height' => 500])
            ->setType(6, 'Neopixel', 'neopixel', uiSettings: ['icon' => 'icon_led'])
            ->setType(7, 'IR', 'ir', hasInput: true, uiSettings: ['icon' => 'icon_remotecontrol'])
            ->setType(8, 'I/O', 'io', hasInput: true)
            ->setType(9, 'Warehouse', 'warehouse')
            ->setType(256, 'BME 280', 'bme280', isHcSlave: false)
            ->setType(257, 'SSD1306', 'ssd1306', isHcSlave: false, defaultAddresses: [60])
        ;

        yield new Success('Homecontrol types installed!');
    }

    /**
     * @param int[] $defaultAddresses
     *
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
        array $uiSettings = null,
        array $defaultAddresses = []
    ): TypeData {
        $this->logger->info(sprintf('Add homecontrol type #%d "%s"', $id, $name));
        $type = (new Type())
            ->setId($id)
            ->setName($name)
            ->setHelper($helper)
            ->setHertz($hertz)
            ->setIsHcSlave($isHcSlave)
            ->setHasInput($hasInput)
            ->setUiSettings($uiSettings === null ? null : JsonUtility::encode($uiSettings))
        ;
        $this->modelManager->save($type);

        foreach ($defaultAddresses as $defaultAddress) {
            $this->modelManager->save(
                (new Type\DefaultAddress())
                    ->setType($type)
                    ->setAddress($defaultAddress)
            );
        }

        return $this;
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
