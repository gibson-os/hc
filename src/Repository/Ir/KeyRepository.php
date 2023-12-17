<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Ir;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Core\Wrapper\RepositoryWrapper;
use GibsonOS\Module\Hc\Enum\Ir\Protocol;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Ir\Key\Name;
use JsonException;
use MDO\Dto\Query\Join;
use MDO\Dto\Query\Where;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class KeyRepository extends AbstractRepository
{
    public function __construct(
        RepositoryWrapper $repositoryWrapper,
        #[GetTableName(Key::class)]
        private readonly string $keyTableName,
        #[GetTableName(Name::class)]
        private readonly string $nameTableName,
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
    public function getById(int $id): Key
    {
        return $this->fetchOne('`id`=?', [$id], Key::class);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     */
    public function getByProtocolAddressAndCommand(Protocol $protocol, int $address, int $command): Key
    {
        return $this->fetchOne(
            '`protocol`=? AND `address`=? AND `command`=?',
            [$protocol->name, $address, $command],
            Key::class
        );
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Key[]
     */
    public function findByName(string $name): array
    {
        $selectQuery = $this->getSelectQuery($this->keyTableName, 'k')
            ->addJoin(new Join($this->getTable($this->nameTableName), 'n', '`k`.`id`=`n`.`key_id`'))
            ->addWhere(new Where('`n`.`name` REGEXP ?', [$this->getRegexString($name)]))
            ->setOrder('`n`.`name`')
        ;

        return $this->getModels($selectQuery, Key::class);
    }
}
