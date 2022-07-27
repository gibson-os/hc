<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Warehouse\Box;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item;
use GibsonOS\Module\Hc\Repository\Warehouse\Box\ItemRepository;

class ItemAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly ItemRepository $itemRepository)
    {
    }

    /**
     * @throws SelectError
     *
     * @return Item[]
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->itemRepository->findByNameParts(array_map(
            fn (string $name) => $name . '*',
            explode(' ', $namePart)
        ));
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters): Item
    {
        return $this->itemRepository->getById((int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.warehouse.model.box.Item';
    }
}
