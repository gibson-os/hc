<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\DateTimeService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Store\SlaveStore;

class SlaveController extends AbstractController
{
    /**
     * @throws SaveError
     * @throws SelectError
     * @throws FactoryError
     */
    #[CheckPermission(Permission::MANAGE + Permission::WRITE)]
    public function add(
        ModuleRepository $moduleRepository,
        MasterRepository $masterRepository,
        TypeRepository $typeRepository,
        SlaveFactory $slaveFactory,
        DateTimeService $dateTimeService,
        string $name,
        int $address,
        int $masterId,
        int $typeId,
        bool $withHandshake
    ): AjaxResponse {
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
                ->setType($type)
                ->setAdded($dateTimeService->get())
            ;
            $slave->save();

            if ($withHandshake) {
                $slaveService = $slaveFactory->get($type->getHelper());
                $slaveService->handshake($slave);
            }

            return $this->returnSuccess($slave);
        }
    }

    #[CheckPermission(Permission::READ)]
    public function index(
        SlaveStore $slaveStore,
        int $limit = 100,
        int $start = 0,
        array $sort = [],
        int $masterId = null
    ): AjaxResponse {
        $slaveStore->setLimit($limit, $start);
        $slaveStore->setSortByExt($sort);
        $slaveStore->setMasterId($masterId);

        return $this->returnSuccess($slaveStore->getList(), $slaveStore->getCount());
    }

    /**
     * @param int[] $ids
     *
     * @throws DeleteError
     */
    #[CheckPermission(Permission::MANAGE + Permission::DELETE)]
    public function delete(ModuleRepository $moduleRepository, array $ids): AjaxResponse
    {
        $moduleRepository->deleteByIds($ids);

        return $this->returnSuccess();
    }
}
