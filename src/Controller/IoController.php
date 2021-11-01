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
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use GibsonOS\Module\Hc\Store\Io\DirectConnectStore;
use GibsonOS\Module\Hc\Store\Io\PortStore;

class IoController extends AbstractController
{
    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function set(
        IoService $ioService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $number,
        string $name,
        int $direction,
        int $pullUp,
        int $delay,
        int $pwm,
        int $blink,
        int $fade,
        array $valueNames
    ): AjaxResponse {
        $valueNames = array_map('trim', $valueNames);
        $ioService->setPort(
            $moduleRepository->getById($moduleId),
            $number,
            $name,
            $direction,
            $pullUp,
            $delay,
            $pwm,
            $blink,
            $fade,
            $valueNames
        );

        return $this->returnSuccess();
    }

    #[CheckPermission(Permission::READ)]
    public function ports(PortStore $portStore, int $moduleId): AjaxResponse
    {
        $portStore->setModule($moduleId);

        return $this->returnSuccess($portStore->getList());
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function toggle(
        IoService $ioService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $number
    ): AjaxResponse {
        $ioService->toggleValue($moduleRepository->getById($moduleId), $number);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function loadFromEeprom(IoService $ioService, ModuleRepository $moduleRepository, int $moduleId): AjaxResponse
    {
        $slave = $moduleRepository->getById($moduleId);
        $ioService->readPortsFromEeprom($slave);

        return $this->returnSuccess($ioService->getPorts($slave));
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function saveToEeprom(IoService $ioService, ModuleRepository $moduleRepository, int $moduleId): AjaxResponse
    {
        $ioService->writePortsToEeprom($moduleRepository->getById($moduleId));

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
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $directConnectStore->setModule($moduleId);

        return new AjaxResponse([
            'data' => $directConnectStore->getList(),
            'active' => $ioService->isDirectConnectActive($moduleRepository->getById($moduleId)),
        ]);
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function saveDirectConnect(
        IoService $ioService,
        ModuleRepository $moduleRepository,
        int $moduleId,
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
            $moduleRepository->getById($moduleId),
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
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $inputPort,
        int $order
    ): AjaxResponse {
        $ioService->deleteDirectConnect($moduleRepository->getById($moduleId), $inputPort, $order);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission(Permission::DELETE)]
    public function resetDirectConnect(
        IoService $ioService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $inputPort
    ): AjaxResponse {
        $ioService->resetDirectConnect($moduleRepository->getById($moduleId), $inputPort);

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
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $inputPort,
        int $order,
        bool $reset
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);

        try {
            if ($reset) {
                $ioService->resetDirectConnect($slave, $inputPort, true);
            }

            return $this->returnSuccess($ioService->readDirectConnect($slave, $inputPort, $order));
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
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $ioService->defragmentDirectConnect($moduleRepository->getById($moduleId));

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
        ModuleRepository $moduleRepository,
        int $moduleId,
        bool $activate
    ): AjaxResponse {
        $ioService->activateDirectConnect($moduleRepository->getById($moduleId), $activate);

        return $this->returnSuccess();
    }
}
