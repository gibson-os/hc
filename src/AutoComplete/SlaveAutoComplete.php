<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class SlaveAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly ModuleRepository $moduleRepository)
    {
    }

    /**
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        $typeId = $parameters['typeId'] ?? null;

        return $this->moduleRepository->findByName($namePart, $typeId === null ? $typeId : (int) $typeId);
    }

    /**
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

    public function getValueField(): string
    {
        return 'id';
    }

    public function getDisplayField(): string
    {
        return 'name';
    }
}
