<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Mapper\Bme280Mapper;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Slave\Bme280Service;
use JsonException;

class Bme280Controller extends AbstractController
{
    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ)]
    public function measure(
        Bme280Service $bme280Service,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);

        return $this->returnSuccess($bme280Service->measure($slave));
    }

    /**
     * @throws SelectError
     * @throws JsonException
     */
    #[CheckPermission(Permission::READ)]
    public function status(
        Bme280Mapper $bme280Mapper,
        ModuleRepository $moduleRepository,
        LogRepository $logRepository,
        int $moduleId
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $log = $logRepository->getLastEntryByModuleId($moduleId, Bme280Service::COMMAND_MEASURE);

        return $this->returnSuccess($bme280Mapper->measureData(
            $log->getRawData(),
            JsonUtility::decode((string) $slave->getConfig())
        ));
    }
}
