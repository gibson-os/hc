<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
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
use GibsonOS\Module\Hc\Attribute\GetAttribute;
use GibsonOS\Module\Hc\Dto\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use GibsonOS\Module\Hc\Store\Io\DirectConnectStore;
use GibsonOS\Module\Hc\Store\Io\PortStore;
use JsonException;
use ReflectionException;

class IoController extends AbstractController
{
    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function set(
        IoService $ioService,
        // Required to get port attribute
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetAttribute] Port $port,
    ): AjaxResponse {
        $ioService->setPort($port);
        $ioService->pushUpdate($module, [$port]);

        return $this->returnSuccess();
    }

    /**
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     * @throws MapperException
     *
     * @return AjaxResponse
     */
    #[CheckPermission(Permission::READ)]
    public function ports(
        PortStore $portStore,
        #[GetModel(['id' => 'moduleId'])] Module $module,
    ): AjaxResponse {
        $portStore->setModule($module);

        return $this->returnSuccess($portStore->getList());
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function toggle(
        IoService $ioService,
        // Required to get port attribute
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetAttribute(['fade' => 'fadeIn'])] Port $port
    ): AjaxResponse {
        $ioService->toggleValue($port);
        $ioService->pushUpdate($module, [$port]);

        return $this->returnSuccess();
    }

    /**
     * @param IoService $ioService
     * @param Module    $module
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     *
     * @return AjaxResponse
     */
    #[CheckPermission(Permission::WRITE)]
    public function loadFromEeprom(IoService $ioService, #[GetModel(['id' => 'moduleId'])] Module $module): AjaxResponse
    {
        $ioService->readPortsFromEeprom($module);

        return $this->returnSuccess($ioService->getPorts($module));
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function saveToEeprom(IoService $ioService, #[GetModel(['id' => 'moduleId'])] Module $module): AjaxResponse
    {
        $ioService->writePortsToEeprom($module);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ)]
    public function directConnects(
        IoService $ioService,
        DirectConnectStore $directConnectStore,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        $directConnectStore->setModule($module);

        return new AjaxResponse([
            'data' => $directConnectStore->getList(),
            'active' => $ioService->isDirectConnectActive($module),
        ]);
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function saveDirectConnect(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        int $inputPort,
        int $inputPortValue,
        int $outputPort,
        int $order,
        int $pwm,
        int $blink,
        int $fadeIn,
        int $value,
        int $addOrSub
    ): AjaxResponse {
        $ioService->saveDirectConnect(
            $module,
            $inputPort,
            $inputPortValue,
            $order,
            $outputPort,
            $value,
            $pwm,
            $blink,
            $fadeIn,
            $addOrSub
        );

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission(Permission::DELETE)]
    public function deleteDirectConnect(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        int $inputPort,
        int $order
    ): AjaxResponse {
        $ioService->deleteDirectConnect($module, $inputPort, $order);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission(Permission::DELETE)]
    public function resetDirectConnect(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        int $inputPort
    ): AjaxResponse {
        $ioService->resetDirectConnect($module, $inputPort);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ)]
    public function readDirectConnect(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        int $inputPort,
        int $order,
        bool $reset
    ): AjaxResponse {
        try {
            if ($reset) {
                $ioService->resetDirectConnect($module, $inputPort, true);
            }

            return $this->returnSuccess($ioService->readDirectConnect($module, $inputPort, $order));
        } catch (ReceiveError $exception) {
            if ($exception->getCode() === IoService::DIRECT_CONNECT_READ_NOT_EXIST) {
                return $this->returnSuccess();
            }

            throw $exception;
        }
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function defragmentDirectConnect(
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
     */
    #[CheckPermission(Permission::WRITE)]
    public function activateDirectConnect(
        IoService $ioService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        bool $activate
    ): AjaxResponse {
        $ioService->activateDirectConnect($module, $activate);

        return $this->returnSuccess();
    }
}
