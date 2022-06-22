<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Ir;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Ir\Remote;

class RemoteRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public function getByName(string $name): Remote
    {
        return $this->fetchOne('`name`=?', [$name], Remote::class);
    }
}
