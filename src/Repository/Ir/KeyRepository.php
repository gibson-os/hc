<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Ir;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Dto\Ir\Protocol;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Ir\Key\Name;

class KeyRepository extends AbstractRepository
{
    public function __construct(
        #[GetTableName(Key::class)] private readonly string $keyTableName,
        #[GetTableName(Name::class)] private readonly string $nameTableName,
    ) {
    }

    /**
     * @throws SelectError
     */
    public function getById(int $id): Key
    {
        return $this->fetchOne('`id`=?', [$id], Key::class);
    }

    /**
     * @throws SelectError
     */
    public function getByProtocolAddressAndCommand(Protocol $protocol, int $address, int $command): Key
    {
        return $this->fetchOne(
            '`protocol`=? AND `address`=? AND `command`=?',
            [$protocol, $address, $command],
            Key::class
        );
    }

    /**
     * @throws SelectError
     *
     * @return Key[]
     */
    public function findByName(string $name): array
    {
        $table = $this->getTable($this->keyTableName)
            ->appendJoin(
                $this->nameTableName,
                sprintf('`%s`.`id`=`%s`.`key_id`', $this->keyTableName, $this->nameTableName)
            )
            ->setWhere(sprintf('`%s`.`name` REGEXP ?', $this->nameTableName))
            ->addWhereParameter($this->getRegexString($name))
            ->setOrderBy(sprintf('`%s`.`name`', $this->nameTableName))
        ;

        return $this->getModels($table, Key::class);
    }
}
