<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\LoginRequired;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\PermissionDenied;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\PermissionService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use GibsonOS\Module\Hc\Store\Io\DirectConnectStore;
use GibsonOS\Module\Hc\Store\Io\PortStore;

class IoController extends AbstractController
{
    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     */
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
        $this->checkPermission(PermissionService::WRITE);

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

    /**
     * @throws LoginRequired
     * @throws PermissionDenied
     */
    public function ports(PortStore $portStore, int $moduleId): AjaxResponse
    {
        $this->checkPermission(PermissionService::READ);

        $portStore->setModule($moduleId);

        return $this->returnSuccess($portStore->getList());
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     */
    public function toggle(
        IoService $ioService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $number
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        $ioService->toggleValue($moduleRepository->getById($moduleId), $number);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     * @throws SaveError
     * @throws ReceiveError
     */
    public function loadFromEeprom(IoService $ioService, ModuleRepository $moduleRepository, int $moduleId): AjaxResponse
    {
        $this->checkPermission(PermissionService::WRITE);

        $slave = $moduleRepository->getById($moduleId);
        $ioService->readPortsFromEeprom($slave);

        return $this->returnSuccess($ioService->getPorts($slave));
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     * @throws SelectError
     */
    public function saveToEeprom(IoService $ioService, ModuleRepository $moduleRepository, int $moduleId): AjaxResponse
    {
        $this->checkPermission(PermissionService::WRITE);

        $ioService->writePortsToEeprom($moduleRepository->getById($moduleId));

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     */
    public function directConnects(
        IoService $ioService,
        DirectConnectStore $directConnectStore,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $this->checkPermission(PermissionService::READ);

        $directConnectStore->setModule($moduleId);

        return new AjaxResponse([
            'data' => $directConnectStore->getList(),
            'active' => $ioService->isDirectConnectActive($moduleRepository->getById($moduleId)),
        ]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     */
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
        $this->checkPermission(PermissionService::WRITE);

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
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     */
    public function deleteDirectConnect(
        IoService $ioService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $inputPort,
        int $order
    ): AjaxResponse {
        $this->checkPermission(PermissionService::DELETE);

        $ioService->deleteDirectConnect($moduleRepository->getById($moduleId), $inputPort, $order);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     */
    public function resetDirectConnect(
        IoService $ioService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $inputPort
    ): AjaxResponse {
        $this->checkPermission(PermissionService::DELETE);

        $ioService->resetDirectConnect($moduleRepository->getById($moduleId), $inputPort);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     */
    public function readDirectConnect(
        IoService $ioService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $inputPort,
        int $order,
        bool $reset
    ): AjaxResponse {
        $this->checkPermission(PermissionService::READ);

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
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SaveError
     * @throws SelectError
     */
    public function defragmentDirectConnect(
        IoService $ioService,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $this->checkPermission(PermissionService::READ);

        $ioService->defragmentDirectConnect($moduleRepository->getById($moduleId));

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     * @throws SelectError
     */
    public function activateDirectConnect(
        IoService $ioService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        bool $activate
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        $ioService->activateDirectConnect($moduleRepository->getById($moduleId), $activate);

        return $this->returnSuccess();
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     */
    public function autoCompletePort(
        ModuleRepository $moduleRepository,
        ValueRepository $valueRepository,
        int $moduleId,
        int $id,
        string $name = null
    ): AjaxResponse {
        $this->checkPermission(PermissionService::READ);

        $slave = $moduleRepository->getById($moduleId);

        if ($id !== null) {
            $ports = [$valueRepository->getByTypeId(
                $slave->getTypeId(),
                $id,
                [(int) $slave->getId()],
                IoService::ATTRIBUTE_TYPE_PORT
            )];
        } else {
            try {
                $valueRepository = new ValueRepository();
                $ports = $valueRepository->findAttributesByValue(
                    $name . '*',
                    $slave->getTypeId(),
                    [IoService::ATTRIBUTE_PORT_KEY_NAME],
                    [$slave->getId()],
                    null,
                    IoService::ATTRIBUTE_TYPE_PORT
                );
            } catch (SelectError $e) {
                $ports = [];
            }
        }

        $data = [];

        foreach ($ports as $port) {
            if ($port->getAttribute()->getKey() !== IoService::ATTRIBUTE_PORT_KEY_NAME) {
                continue;
            }

            $data[] = [
                'id' => $port->getAttribute()->getSubId(),
                'name' => $port->getValue(),
            ];
        }

        return $this->returnSuccess($data);
    }
}
