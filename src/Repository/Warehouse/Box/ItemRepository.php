<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Warehouse\Box;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Core\Wrapper\RepositoryWrapper;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item;
use GibsonOS\Module\Hc\Model\Warehouse\Tag;
use JsonException;
use MDO\Dto\Query\Join;
use MDO\Dto\Query\Where;
use MDO\Enum\OrderDirection;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class ItemRepository extends AbstractRepository
{
    public function __construct(
        RepositoryWrapper $repositoryWrapper,
        #[GetTableName(Item::class)]
        private readonly string $itemTableName,
        #[GetTableName(Item\Tag::class)]
        private readonly string $itemTagTableName,
        #[GetTableName(Tag::class)]
        private readonly string $tagTableName,
    ) {
        parent::__construct($repositoryWrapper);
    }

    /**
     * @throws SelectError
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     */
    public function getById(int $id): Item
    {
        return $this->fetchOne('`id`=?', [$id], Item::class);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Item[]
     */
    public function findByName(string $name): array
    {
        return $this->fetchAll(
            '`name` REGEXP ?',
            [$this->getRegexString($name)],
            Item::class,
            orderBy: ['`name`' => OrderDirection::ASC],
        );
    }

    /**
     * @param string[] $nameParts
     *
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Item[]
     */
    public function findByNameParts(array $nameParts): array
    {
        $nameParts = array_map(
            fn (string $namePart) => $this->getRegexString($namePart),
            $nameParts
        );

        $selectQuery = $this->getSelectQuery($this->itemTableName, 'i')
            ->addJoin(new Join($this->getTable($this->itemTagTableName), 'it', '`i`.`id`=`it`.`item_id`'))
            ->addJoin(new Join($this->getTable($this->tagTableName), 't', '`t`.`id`=`it`.`tag_id`'))
            ->addWhere(new Where(
                sprintf(
                    '(`i`.`name` REGEXP %s) OR (`t`.`name` REGEXP %s)',
                    $this->getRepositoryWrapper()->getSelectService()->getParametersString($nameParts, ' AND `i`.`name` REGEXP '),
                    $this->getRepositoryWrapper()->getSelectService()->getParametersString($nameParts, ' AND `t`.`name` REGEXP '),
                ),
                array_merge($nameParts, $nameParts),
            ))
            ->setOrders(['`t`.`name`' => OrderDirection::ASC, '`i`.`name`' => OrderDirection::ASC])
        ;

        return $this->getModels($selectQuery, Item::class);
    }
}
