<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event\AutoComplete;

use GibsonOS\Core\Event\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\ModelInterface;
use GibsonOS\Module\Hc\Repository\ModuleRepository;

class SlaveAutoComplete implements AutoCompleteInterface
{
    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    public function __construct(ModuleRepository $moduleRepository)
    {
        $this->moduleRepository = $moduleRepository;
    }

    /**
     * @throws DateTimeError
     * @throws GetError
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
     * @throws GetError
     * @throws SelectError
     */
    public function getById($id, array $parameters = []): ModelInterface
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
}
