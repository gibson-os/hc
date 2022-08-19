<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Warehouse\Label;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Repository\Warehouse\Label\TemplateRepository;

class TemplateAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly TemplateRepository $templateRepository)
    {
    }

    /**
     * @throws SelectError
     *
     * @return AutoCompleteModelInterface[]
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->templateRepository->findByName($namePart . '*');
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters): AutoCompleteModelInterface
    {
        return $this->templateRepository->getById((int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.warehouse.model.Label';
    }
}
