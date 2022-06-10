<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Io;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;

class PortRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     *
     * @return Port[]
     */
    public function getByModule(Module $module): array
    {
        return $this->fetchAll('`module_id`=?', [$module->getId()], Port::class);
    }
}
