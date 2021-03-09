<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\AutoCompleteException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\ModelInterface;
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
     * @param int $id
     *
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getById($id, array $parameters = []): Module
    {
        return $this->moduleRepository->getById($id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.index.model.Module';
    }

    public function getParameters(): array
    {
        return [];
    }

    /**
     * @throws AutoCompleteException
     */
    public function getIdFromModel(ModelInterface $model): int
    {
        if (!$model instanceof Module) {
            throw new AutoCompleteException(sprintf('Model is not instance of %s', Module::class));
        }

        return $model->getId() ?? 0;
    }
}
