<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Blueprint;
use GibsonOS\Module\Hc\Repository\BlueprintRepository;

class BlueprintAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly BlueprintRepository $blueprintRepository)
    {
    }

    /**
     * @throws SelectError
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->blueprintRepository->findByName($namePart);
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters): Blueprint
    {
        return $this->blueprintRepository->getById((int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.blueprint.model.Blueprint';
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
