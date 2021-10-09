<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\LoginRequired;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\PermissionDenied;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\PermissionService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;

class SlaveController extends AbstractController
{
    /**
     * @throws DateTimeError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SaveError
     * @throws SelectError
     * @throws FactoryError
     */
    public function add(
        ModuleRepository $moduleRepository,
        MasterRepository $masterRepository,
        TypeRepository $typeRepository,
        SlaveFactory $slaveFactory,
        string $name,
        int $address,
        int $masterId,
        int $typeId,
        bool $withHandshake
    ): AjaxResponse {
        $this->checkPermission(PermissionService::MANAGE + PermissionService::WRITE);

        $master = $masterRepository->getById($masterId);
        $type = $typeRepository->getById($typeId);

        try {
            $slave = $moduleRepository->getByAddress($address, $masterId);

            return $this->returnFailure(sprintf(
                'Unter der Adresse %d existiert bereits ein Modul (%s)',
                $address,
                $slave->getName()
            ));
        } catch (SelectError) {
            $slave = (new Module())
                ->setName($name)
                ->setAddress($address)
                ->setMaster($master)
                ->setType($type);
            $slave->save();

            if ($withHandshake) {
                $slaveService = $slaveFactory->get($type->getHelper());
                $slaveService->handshake($slave);
            }

            return $this->returnSuccess($slave);
        }
    }
}
