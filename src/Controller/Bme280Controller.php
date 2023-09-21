<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Mapper\Bme280Mapper;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\Module\Bme280Service;
use JsonException;

class Bme280Controller extends AbstractController
{
    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     * @throws JsonException
     */
    #[CheckPermission([Permission::READ])]
    public function getMeasure(Bme280Service $bme280Service, #[GetModel(['id' => 'moduleId'])] Module $module): AjaxResponse
    {
        return $this->returnSuccess($bme280Service->measure($module));
    }

    /**
     * @throws SelectError
     * @throws JsonException
     */
    #[CheckPermission([Permission::READ])]
    public function get(
        Bme280Mapper $bme280Mapper,
        LogRepository $logRepository,
        #[GetModel(['id' => 'moduleId'])]
        Module $module
    ): AjaxResponse {
        $log = $logRepository->getLastEntryByModuleId(
            $module->getId() ?? 0,
            Bme280Service::COMMAND_MEASURE
        );

        return $this->returnSuccess($bme280Mapper->measureData(
            $log->getRawData(),
            JsonUtility::decode((string) $module->getConfig())
        ));
    }
}
