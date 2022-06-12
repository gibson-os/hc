<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Ir;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Repository\Ir\KeyRepository;

class KeyAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly KeyRepository $keyRepository)
    {
    }

    /**
     * @throws SelectError
     *
     * @return Key[]
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->keyRepository->findByName($namePart . '*');
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters): Key
    {
        return $this->keyRepository->getById((int) $id);
    }

    public function getParameters(): array
    {
        return [];
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.ir.model.Key';
    }
}
