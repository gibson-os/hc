<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Warehouse;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Repository\Warehouse\LabelRepository;

class LabelAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly LabelRepository $labelRepository)
    {
    }

    /**
     * @throws SelectError
     *
     * @return AutoCompleteModelInterface[]
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->labelRepository->findByName($namePart . '*');
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters): AutoCompleteModelInterface
    {
        return $this->labelRepository->getById((int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.warehouse.model.Label';
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
