<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Install;

use Generator;
use GibsonOS\Core\Dto\Install\Success;
use GibsonOS\Core\Install\AbstractInstall;
use GibsonOS\Core\Service\InstallService;
use GibsonOS\Core\Service\PriorityInterface;

class FilePathInstall extends AbstractInstall implements PriorityInterface
{
    public function install(string $module): Generator
    {
        yield $filePathInput = $this->getSettingInput(
            'hc',
            'hc_file_path',
            'What is the directory for homecontrol files?'
        );
        $value = $filePathInput->getValue() ?? '';

        if (!file_exists($value)) {
            $this->dirService->create($value);
        }

        $this->setSetting('hc', 'hc_file_path', $value);

        yield new Success('Homecontrol directory is set!');
    }

    public function getPart(): string
    {
        return InstallService::PART_CONFIG;
    }

    public function getModule(): ?string
    {
        return 'hc';
    }

    public function getPriority(): int
    {
        return 500;
    }
}
