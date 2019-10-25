<?php
namespace GibsonOS\Module\Hc\Utility;

use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Module as ModuleModel;
use GibsonOS\Module\Hc\Repository\Module as ModuleRepository;

class Slave
{
    /**
     * @param int $slaveId
     * @param string $helperName
     * @return ModuleModel
     * @throws GetError
     * @throws SelectError
     */
    public static function getModelById($slaveId, $helperName)
    {
        $slaveModel = ModuleRepository::getById($slaveId);
        $slaveModel->loadType();

        if ($slaveModel->getType()->getHelper() != $helperName) {
            throw new GetError('Slave passt nicht zum Typ');
        }

        return $slaveModel;
    }
}