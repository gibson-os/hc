<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Ir;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Dto\Ir\Protocol;
use GibsonOS\Module\Hc\Model\Ir\Key;

class KeyRepository extends AbstractRepository
{
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
        return $this->fetchAll(
            '`name` REGEXP ?',
            [$this->getRegexString($name)],
            Key::class
        );
    }
}
