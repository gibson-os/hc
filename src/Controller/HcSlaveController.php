<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use Exception;
use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Exception\SetError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Service\ServiceManagerService;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;

class HcSlaveController extends AbstractController
{
    /**
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ + Permission::MANAGE)]
    public function generalSettings(
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);

        return $this->returnSuccess([
            'name' => $slave->getName(),
            'hertz' => $slave->getHertz(),
            'bufferSize' => $slave->getBufferSize(),
            'deviceId' => $slave->getDeviceId(),
            'typeId' => $slave->getTypeId(),
            'address' => $slave->getAddress(),
            'pwmSpeed' => $slave->getPwmSpeed(),
        ]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws FactoryError
     * @throws SelectError
     * @throws Exception
     */
    #[CheckPermission(Permission::WRITE + Permission::MANAGE)]
    public function saveGeneralSettings(
        ServiceManagerService $serviceManagerService,
        ModuleRepository $moduleRepository,
        TypeRepository $typeRepository,
        int $moduleId,
        string $name,
        int $deviceId,
        int $typeId,
        int $address,
        int $pwmSpeed = null,
        bool $overwriteSlave = false,
        bool $deleteSlave = false
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $slaveService = $this->getSlaveService($serviceManagerService, $slave);
        $moduleRepository->startTransaction();

        try {
            $slave
                ->setName($name)
                ->save()
            ;
            $moduleRepository->commit();
            $moduleRepository->startTransaction();

            if ($deviceId !== $slave->getDeviceId()) {
                try {
                    $existingSlave = $moduleRepository->getByDeviceId($deviceId);

                    if ($overwriteSlave) {
                        $slave->setType($existingSlave->getType());
                        $slaveService = $this->getSlaveService($serviceManagerService, $slave);
                        $slaveService->onOverwriteExistingSlave($slave, $existingSlave);
                        $moduleRepository->deleteByIds([(int) $slave->getId()]);
                        $slave->setId((int) $existingSlave->getId());
                        $typeId = $slave->getTypeId();
                    // @todo log umschreiben?
                    } elseif ($deleteSlave) {
                        $moduleRepository->deleteByIds([(int) $existingSlave->getId()]);
                    } else {
                        $exception = new SetError('Device ID ' . $deviceId . ' ist schon in benutzung!');
                        $exception->setType(AbstractException::QUESTION);
                        $exception->setExtraParameter('moduleId', $moduleId);
                        $exception->addButton('Vorhandenes Modul überschreiben', 'overwriteSlave', true);
                        $exception->addButton('Vorhandenes Modul entfernen', 'deleteSlave', true);
                        $exception->addButton('Abbrechen');

                        throw $exception;
                    }
                } catch (SelectError) {
                    // No existing slave
                }

                $slaveService->writeDeviceId($slave, $deviceId);
                $slave->save();
                $moduleRepository->commit();
                $moduleRepository->startTransaction();
            }

            if ($address !== $slave->getAddress()) {
                $slaveService->writeAddress($slave, $address);
                $slave->save();
                $moduleRepository->commit();
                $moduleRepository->startTransaction();
            }

            if ($pwmSpeed !== $slave->getPwmSpeed()) {
                if ($pwmSpeed !== null) {
                    $slaveService->writePwmSpeed($slave, $pwmSpeed);
                }

                $slave
                    ->setPwmSpeed($pwmSpeed)
                    ->save()
                ;
                $moduleRepository->commit();
                $moduleRepository->startTransaction();
            }

            if ($typeId !== $slave->getTypeId()) {
                $type = $typeRepository->getById($typeId);
                $slaveService->writeTypeId($slave, $type);
                $slave->save();
                $moduleRepository->commit();
            }
        } catch (AbstractException $exception) {
            $moduleRepository->rollback();

            throw $exception;
        }

        return $this->returnSuccess([
            'id' => $slave->getId(),
            'name' => $slave->getName(),
            'type_id' => $slave->getTypeId(),
            'address' => $slave->getAddress(),
            'hertz' => $slave->getHertz(),
            'offline' => false,
            'added' => $slave->getAdded(),
            'modified' => $slave->getModified(),
            'type' => $slave->getType()->getName(),
            'settings' => $slave->getType()->getUiSettings(),
            'helper' => $slave->getType()->getHelper(),
        ]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws FactoryError
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ + Permission::MANAGE)]
    public function eepromSettings(
        ServiceManagerService $serviceManagerService,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $slaveService = $this->getSlaveService($serviceManagerService, $slave);

        return $this->returnSuccess([
            'size' => $slave->getEepromSize(),
            'free' => $slaveService->readEepromFree($slave),
            'position' => $slaveService->readEepromPosition($slave),
        ]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws FactoryError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE + Permission::MANAGE)]
    public function saveEepromSettings(
        ServiceManagerService $serviceManagerService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $position
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $slaveService = $this->getSlaveService($serviceManagerService, $slave);
        $slaveService->writeEepromPosition($slave, $position);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws FactoryError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::DELETE + Permission::MANAGE)]
    public function eraseEeprom(
        ServiceManagerService $serviceManagerService,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $slaveService = $this->getSlaveService($serviceManagerService, $slave);
        $slaveService->writeEepromErase($slave);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws FactoryError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE + Permission::MANAGE)]
    public function restart(
        ServiceManagerService $serviceManagerService,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $slaveService = $this->getSlaveService($serviceManagerService, $slave);
        $slaveService->writeRestart($slave);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws FactoryError
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ + Permission::MANAGE)]
    public function getStatusLeds(
        ServiceManagerService $serviceManagerService,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $slaveService = $this->getSlaveService($serviceManagerService, $slave);
        $activeLeds = $slaveService->readLedStatus($slave);
        $leds = ['exist' => $activeLeds];

        foreach ($activeLeds as $led => $active) {
            if (
                !$active ||
                $led === AbstractHcSlave::RGB_LED_KEY
            ) {
                continue;
            }

            $leds = array_merge($slaveService->readAllLeds($slave), $leds);

            break;
        }

        if ($activeLeds[AbstractHcSlave::RGB_LED_KEY]) {
            foreach ($slaveService->readRgbLed($slave) as $rgbLed => $code) {
                $leds[$rgbLed . 'Code'] = $code;
            }
        }

        return $this->returnSuccess($leds);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws FactoryError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE + Permission::MANAGE)]
    public function setStatusLeds(
        ServiceManagerService $serviceManagerService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        bool $power = false,
        bool $error = false,
        bool $connect = false,
        bool $transreceive = false,
        bool $transceive = false,
        bool $receive = false,
        bool $custom = false,
        string $powerCode = null,
        string $errorCode = null,
        string $connectCode = null,
        string $transceiveCode = null,
        string $receiveCode = null,
        string $customCode = null
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $slaveService = $this->getSlaveService($serviceManagerService, $slave);

        $slaveService->writeAllLeds(
            $slave,
            $power,
            $error,
            $connect,
            $transreceive,
            $transceive,
            $receive,
            $custom
        );

        if (
            $powerCode !== null ||
            $errorCode !== null ||
            $connectCode !== null ||
            $transceiveCode !== null ||
            $receiveCode !== null ||
            $customCode !== null
        ) {
            $slaveService->writeRgbLed(
                $slave,
                $powerCode ?: '000',
                $errorCode ?: '000',
                $connectCode ?: '000',
                $transceiveCode ?: '000',
                $receiveCode ?: '000',
                $customCode ?: '000'
            );
        }

        return $this->returnSuccess();
    }

    /**
     * @throws FactoryError
     */
    private function getSlaveService(ServiceManagerService $serviceManagerService, Module $slave): AbstractHcSlave
    {
        /** @var class-string $className */
        $className = 'GibsonOS\\Module\\Hc\\Service\\Slave\\' . ucfirst($slave->getType()->getHelper()) . 'Service';
        /** @var AbstractHcSlave $service */
        $service = $serviceManagerService->get($className);

        return $service;
    }
}
