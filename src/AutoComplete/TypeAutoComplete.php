<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\TypeRepository;

class TypeAutoComplete implements AutoCompleteInterface
{
    public function __construct(private TypeRepository $typeRepository)
    {
    }

    /**
     * @throws SelectError
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->typeRepository->findByName($namePart, isset($parameters['onlyHcSlave']));
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters = []): Type
    {
        return $this->typeRepository->getById((int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.index.model.Type';
    }
}
