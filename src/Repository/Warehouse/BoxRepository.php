<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Warehouse;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Core\Wrapper\RepositoryWrapper;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use JsonException;
use MDO\Dto\Query\Where;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class BoxRepository extends AbstractRepository
{
    public function __construct(
        RepositoryWrapper $repositoryWrapper,
        #[GetTableName(Box::class)]
        private readonly string $boxTableName
    ) {
        parent::__construct($repositoryWrapper);
    }

    /**
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Box[]
     */
    public function getByModule(Module $module): array
    {
        return $this->fetchAll('`module_id`=?', [$module->getId()], Box::class);
    }

    /**
     * @throws ClientException
     * @throws RecordException
     * @throws SelectError
     */
    public function getFreeUuid(): string
    {
        $selectQuery = $this->getSelectQuery($this->boxTableName);

        while (true) {
            $uuid = mb_substr(md5((string) mt_rand()), 0, 8);
            $selectQuery->setWheres([new Where('`uuid`=?', [$uuid])]);

            $aggregations = $this->getAggregations(
                ['count' => 'COUNT(`id`)'],
                Box::class,
                '`uuid`=?',
                [$uuid],
            );

            if ((int) $aggregations->get('count')->getValue() === 0) {
                return $uuid;
            }
        }
    }
}
