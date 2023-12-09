<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class TypeAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly TypeRepository $typeRepository)
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

    public function getValueField(): string
    {
        return 'id';
    }

    public function getDisplayField(): string
    {
        return 'name';
    }
}
