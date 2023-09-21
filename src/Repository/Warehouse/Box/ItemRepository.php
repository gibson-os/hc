<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Warehouse\Box;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item;
use GibsonOS\Module\Hc\Model\Warehouse\Tag;

class ItemRepository extends AbstractRepository
{
    public function __construct(
        #[GetTableName(Item::class)]
        private readonly string $itemTableName,
        #[GetTableName(Item\Tag::class)]
        private readonly string $itemTagTableName,
        #[GetTableName(Tag::class)]
        private readonly string $tagTableName,
    ) {
    }

    /**
     * @throws SelectError
     */
    public function getById(int $id): Item
    {
        return $this->fetchOne('`id`=?', [$id], Item::class);
    }

    /**
     * @throws SelectError
     *
     * @return Item[]
     */
    public function findByName(string $name): array
    {
        return $this->fetchAll(
            '`name` REGEXP ?',
            [$this->getRegexString($name)],
            Item::class,
            orderBy: '`name`'
        );
    }

    /**
     * @param string[] $nameParts
     *
     * @throws SelectError
     *
     * @return Item[]
     */
    public function findByNameParts(array $nameParts): array
    {
        $nameParts = array_map(
            fn (string $namePart) => $this->getRegexString($namePart),
            $nameParts
        );

        $table = $this->getTable($this->itemTableName);
        $table
            ->appendJoinLeft(
                $this->itemTagTableName,
                sprintf('`%s`.`id`=`%s`.`item_id`', $this->itemTableName, $this->itemTagTableName)
            )
            ->appendJoinLeft(
                $this->tagTableName,
                sprintf('`%s`.`id`=`%s`.`tag_id`', $this->tagTableName, $this->itemTagTableName)
            )
            ->setWhere(sprintf(
                '(`%s`.`name` REGEXP %s) OR (`%s`.`name` REGEXP %s)',
                $this->itemTableName,
                $table->getParametersString(
                    $nameParts,
                    sprintf(' AND `%s`.`name` REGEXP ', $this->itemTableName)
                ),
                $this->tagTableName,
                $table->getParametersString(
                    $nameParts,
                    sprintf(' AND `%s`.`name` REGEXP ', $this->tagTableName)
                )
            ))
            ->setWhereParameters(array_merge($nameParts, $nameParts))
            ->setOrderBy(sprintf(
                '`%s`.`name`, `%s`.`name`',
                $this->tagTableName,
                $this->itemTableName,
            ))
        ;

        return $this->getModels($table, Item::class);
    }
}
