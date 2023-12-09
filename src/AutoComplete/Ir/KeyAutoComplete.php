<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Ir;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Repository\Ir\KeyRepository;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class KeyAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly KeyRepository $keyRepository)
    {
    }

    /**
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
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

    public function getValueField(): string
    {
        return 'id';
    }

    public function getDisplayField(): string
    {
        return 'name';
    }
}
