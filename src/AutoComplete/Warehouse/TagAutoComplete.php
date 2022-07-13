<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Warehouse;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Warehouse\Tag;
use GibsonOS\Module\Hc\Repository\Warehouse\TagRepository;

class TagAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly TagRepository $tagRepository)
    {
    }

    /**
     * @throws SelectError
     *
     * @return Tag[]
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->tagRepository->findByName($namePart);
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters): Tag
    {
        return $this->tagRepository->getById((int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.warehouse.model.box.item.Tag';
    }
}
