<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\ModuleRepository;

class SlaveAutoComplete implements AutoCompleteInterface
{
    private ModuleRepository $moduleRepository;

    public function __construct(ModuleRepository $moduleRepository)
    {
        $this->moduleRepository = $moduleRepository;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->moduleRepository->findByName($namePart, (int) $parameters['typeId']);
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getById(string $id, array $parameters = []): Module
    {
        return $this->moduleRepository->getById((int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.index.model.Module';
    }

    public function getParameters(): array
    {
        return [];
    }
}
