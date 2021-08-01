<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\MasterRepository;

class MasterAutoComplete implements AutoCompleteInterface
{
    private MasterRepository $masterRepository;

    public function __construct(MasterRepository $masterRepository)
    {
        $this->masterRepository = $masterRepository;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->masterRepository->findByName($namePart);
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getById(string $id, array $parameters = []): Type
    {
        return $this->masterRepository->getById((int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.index.model.Master';
    }
}
