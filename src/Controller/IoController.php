<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModel;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Io\PortRepository;
use GibsonOS\Module\Hc\Service\Module\IoService;
use GibsonOS\Module\Hc\Store\Io\PortStore;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class IoController extends AbstractController
{
    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission([Permission::WRITE])]
    public function post(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
        #[GetMappedModel(mapping: ['module' => 'module'])]
        Port $port,
    ): AjaxResponse {
        $ioService->setPort($port);
        $ioService->pushUpdate($module, [$port]);

        return $this->returnSuccess();
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     * @throws ClientException
     * @throws RecordException
     */
    #[CheckPermission([Permission::READ])]
    public function getPorts(
        PortStore $portStore,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
    ): AjaxResponse {
        $portStore->setModule($module);

        return $this->returnSuccess($portStore->getList());
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission([Permission::WRITE])]
    public function postToggle(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
        #[GetModel]
        Port $port,
    ): AjaxResponse {
        $ioService->toggleValue($port);
        $ioService->pushUpdate($module, [$port]);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws ClientException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws RecordException
     * @throws ReflectionException
     * @throws SaveError
     */
    #[CheckPermission([Permission::WRITE])]
    public function getEeprom(
        IoService $ioService,
        PortRepository $portRepository,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
    ): AjaxResponse {
        $ioService->readPortsFromEeprom($module);

        return $this->returnSuccess($portRepository->getByModule($module));
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[CheckPermission([Permission::WRITE])]
    public function postEeprom(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
    ): AjaxResponse {
        $ioService->writePortsToEeprom($module);

        return $this->returnSuccess();
    }
}
