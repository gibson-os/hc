<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModel;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\EventException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\Module\IoService;
use GibsonOS\Module\Hc\Store\Io\DirectConnectStore;

class IoDirectConnectController extends AbstractController
{
    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws \JsonException
     * @throws MapperException
     * @throws ReceiveError
     * @throws \ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ)]
    public function index(
        IoService $ioService,
        DirectConnectStore $directConnectStore,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        $directConnectStore->setModule($module);

        return new AjaxResponse([
            'data' => [...$directConnectStore->getList()],
            'active' => $ioService->isDirectConnectActive($module),
        ]);
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function save(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetModel(['id' => 'inputPortId'])] Port $inputPort,
        #[GetModel(['id' => 'outputPortId'])] Port $outputPort,
        #[GetMappedModel] DirectConnect $directConnect,
    ): AjaxResponse {
        $ioService->saveDirectConnect($module, $directConnect);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     * @throws WriteException
     * @throws \JsonException
     */
    #[CheckPermission(Permission::DELETE)]
    public function delete(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetModel(['id' => 'id'])] DirectConnect $directConnect
    ): AjaxResponse {
        $ioService->deleteDirectConnect($module, $directConnect);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission(Permission::DELETE)]
    public function reset(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetModel(['id' => 'id', 'module_id' => 'moduleId'])] Port $port,
    ): AjaxResponse {
        $ioService->resetDirectConnect($module, $port);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission(Permission::READ)]
    public function read(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetModel(['number' => 'inputPort', 'module_id' => 'moduleId'])] Port $port,
        int $order,
        bool $reset
    ): AjaxResponse {
        if ($reset) {
            $ioService->resetDirectConnect($module, $port, true);
        }

        return $this->returnSuccess($ioService->readDirectConnect($module, $port, $order));
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission(Permission::WRITE)]
    public function defragment(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        $ioService->defragmentDirectConnect($module);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission(Permission::WRITE)]
    public function activate(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        bool $activate
    ): AjaxResponse {
        $ioService->activateDirectConnect($module, $activate);

        return $this->returnSuccess();
    }
}
