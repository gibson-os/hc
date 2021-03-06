<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use Exception;
use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\EventException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Exception\SetError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;
use JsonException;
use ReflectionException;

class HcSlaveController extends AbstractController
{
    #[CheckPermission(Permission::READ + Permission::MANAGE)]
    public function generalSettings(#[GetModel(['id' => 'moduleId'])] Module $module): AjaxResponse
    {
        return $this->returnSuccess([
            'name' => $module->getName(),
            'hertz' => $module->getHertz(),
            'bufferSize' => $module->getBufferSize(),
            'deviceId' => $module->getDeviceId(),
            'typeId' => $module->getTypeId(),
            'address' => $module->getAddress(),
            'pwmSpeed' => $module->getPwmSpeed(),
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
        SlaveFactory $slaveFactory,
        ModuleRepository $moduleRepository,
        TypeRepository $typeRepository,
        ModelManager $modelManager,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        string $name,
        int $deviceId,
        int $typeId,
        int $address,
        int $pwmSpeed = null,
        bool $overwriteSlave = false,
        bool $deleteSlave = false
    ): AjaxResponse {
        $slaveService = $this->getSlaveService($slaveFactory, $module);
        $moduleRepository->startTransaction();

        try {
            $modelManager->save($module->setName($name));
            $moduleRepository->commit();
            $moduleRepository->startTransaction();

            if ($deviceId !== $module->getDeviceId()) {
                try {
                    $existingSlave = $moduleRepository->getByDeviceId($deviceId);

                    if ($overwriteSlave) {
                        $module->setType($existingSlave->getType());
                        $slaveService = $this->getSlaveService($slaveFactory, $module);
                        $slaveService->onOverwriteExistingSlave($module, $existingSlave);
                        $moduleRepository->deleteByIds([$module->getId() ?? 0]);
                        $module->setId((int) $existingSlave->getId());
                        $typeId = $module->getTypeId();
                    // @todo log umschreiben?
                    } elseif ($deleteSlave) {
                        $moduleRepository->deleteByIds([(int) $existingSlave->getId()]);
                    } else {
                        $exception = new SetError('Device ID ' . $deviceId . ' ist schon in benutzung!');
                        $exception->setType(AbstractException::QUESTION);
                        $exception->setExtraParameter('moduleId', $module->getId() ?? 0);
                        $exception->addButton('Vorhandenes Modul ??berschreiben', 'overwriteSlave', true);
                        $exception->addButton('Vorhandenes Modul entfernen', 'deleteSlave', true);
                        $exception->addButton('Abbrechen');

                        throw $exception;
                    }
                } catch (SelectError) {
                    // No existing slave
                }

                $slaveService->writeDeviceId($module, $deviceId);
                $modelManager->save($module);
                $moduleRepository->commit();
                $moduleRepository->startTransaction();
            }

            if ($address !== $module->getAddress()) {
                $slaveService->writeAddress($module, $address);
                $modelManager->save($module);
                $moduleRepository->commit();
                $moduleRepository->startTransaction();
            }

            if ($pwmSpeed !== $module->getPwmSpeed()) {
                if ($pwmSpeed !== null) {
                    $slaveService->writePwmSpeed($module, $pwmSpeed);
                }

                $modelManager->save($module->setPwmSpeed($pwmSpeed));
                $moduleRepository->commit();
                $moduleRepository->startTransaction();
            }

            if ($typeId !== $module->getTypeId()) {
                $type = $typeRepository->getById($typeId);
                $slaveService->writeTypeId($module, $type);
                $modelManager->save($module);
                $moduleRepository->commit();
            }
        } catch (AbstractException $exception) {
            $moduleRepository->rollback();

            throw $exception;
        }

        return $this->returnSuccess([
            'id' => $module->getId(),
            'name' => $module->getName(),
            'type_id' => $module->getTypeId(),
            'address' => $module->getAddress(),
            'hertz' => $module->getHertz(),
            'offline' => false,
            'added' => $module->getAdded(),
            'modified' => $module->getModified(),
            'type' => $module->getType()->getName(),
            'settings' => $module->getType()->getUiSettings(),
            'helper' => $module->getType()->getHelper(),
        ]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws FactoryError
     * @throws ReceiveError
     * @throws SaveError
     * @throws EventException
     * @throws JsonException
     * @throws ReflectionException
     */
    #[CheckPermission(Permission::READ + Permission::MANAGE)]
    public function eepromSettings(
        SlaveFactory $slaveFactory,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        $slaveService = $this->getSlaveService($slaveFactory, $module);

        return $this->returnSuccess([
            'size' => $module->getEepromSize(),
            'free' => $slaveService->readEepromFree($module),
            'position' => $slaveService->readEepromPosition($module),
        ]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    #[CheckPermission(Permission::WRITE + Permission::MANAGE)]
    public function saveEepromSettings(
        SlaveFactory $slaveFactory,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        int $position
    ): AjaxResponse {
        $slaveService = $this->getSlaveService($slaveFactory, $module);
        $slaveService->writeEepromPosition($module, $position);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    #[CheckPermission(Permission::DELETE + Permission::MANAGE)]
    public function eraseEeprom(
        SlaveFactory $slaveFactory,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        $slaveService = $this->getSlaveService($slaveFactory, $module);
        $slaveService->writeEepromErase($module);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    #[CheckPermission(Permission::WRITE + Permission::MANAGE)]
    public function restart(
        SlaveFactory $slaveFactory,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        $slaveService = $this->getSlaveService($slaveFactory, $module);
        $slaveService->writeRestart($module);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[CheckPermission(Permission::READ + Permission::MANAGE)]
    public function getStatusLeds(
        SlaveFactory $slaveFactory,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        $slaveService = $this->getSlaveService($slaveFactory, $module);
        $activeLeds = $slaveService->readLedStatus($module);
        $leds = ['exist' => $activeLeds];

        foreach ($activeLeds as $led => $active) {
            if (
                !$active ||
                $led === AbstractHcSlave::RGB_LED_KEY
            ) {
                continue;
            }

            $leds = array_merge($slaveService->readAllLeds($module), $leds);

            break;
        }

        if ($activeLeds[AbstractHcSlave::RGB_LED_KEY]) {
            foreach ($slaveService->readRgbLed($module) as $rgbLed => $code) {
                $leds[$rgbLed . 'Code'] = $code;
            }
        }

        return $this->returnSuccess($leds);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    #[CheckPermission(Permission::WRITE + Permission::MANAGE)]
    public function setStatusLeds(
        SlaveFactory $slaveFactory,
        #[GetModel(['id' => 'moduleId'])] Module $module,
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
        $slaveService = $this->getSlaveService($slaveFactory, $module);

        $slaveService->writeAllLeds(
            $module,
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
                $module,
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
    private function getSlaveService(SlaveFactory $slaveFactory, Module $slave): AbstractHcSlave
    {
        /** @var AbstractHcSlave $service */
        $service = $slaveFactory->get($slave->getType()->getHelper());

        return $service;
    }
}
