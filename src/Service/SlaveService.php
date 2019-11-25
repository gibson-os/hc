<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Module as ModuleRepository;

class SlaveService extends AbstractService
{
    /**
     * @throws GetError
     * @throws SelectError
     * @throws DateTimeError
     */
    public function getModelById(int $slaveId, string $helperName): Module
    {
        $slaveModel = ModuleRepository::getById($slaveId);

        if ($slaveModel->getType()->getHelper() != $helperName) {
            throw new GetError('Slave passt nicht zum Typ');
        }

        return $slaveModel;
    }
}
