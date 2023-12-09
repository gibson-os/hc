<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class MasterAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly MasterRepository $masterRepository)
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
        return $this->masterRepository->findByName($namePart);
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters = []): Master
    {
        return $this->masterRepository->getById((int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.index.model.Master';
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
