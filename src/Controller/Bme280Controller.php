<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\LoginRequired;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\PermissionDenied;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\PermissionService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Formatter\Bme280Formatter;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Slave\Bme280Service;
use GibsonOS\Module\Hc\Service\TransformService;

class Bme280Controller extends AbstractController
{
    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     */
    public function measure(
        Bme280Service $bme280Service,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $this->checkPermission(PermissionService::READ);

        $slave = $moduleRepository->getById($moduleId);

        return $this->returnSuccess($bme280Service->measure($slave));
    }

    /**
     * @throws DateTimeError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     */
    public function status(
        Bme280Formatter $bme280Formatter,
        ModuleRepository $moduleRepository,
        LogRepository $logRepository,
        TransformService $transformService,
        int $moduleId
    ): AjaxResponse {
        $this->checkPermission(PermissionService::READ);

        $slave = $moduleRepository->getById($moduleId);
        $log = $logRepository->getLastEntryByModuleId($moduleId, Bme280Service::COMMAND_MEASURE);

        return $this->returnSuccess($bme280Formatter->measureData(
            $transformService->hexToAscii($log->getData()),
            JsonUtility::decode((string) $slave->getConfig())
        ));
    }
}
