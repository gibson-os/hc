<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\DateTimeService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Store\ModuleStore;
use JsonException;
use ReflectionException;

class ModuleController extends AbstractController
{
    /**
     * @throws SaveError
     * @throws SelectError
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     */
    #[CheckPermission([Permission::MANAGE, Permission::WRITE])]
    public function post(
        TypeRepository $typeRepository,
        ModuleFactory $moduleFactory,
        DateTimeService $dateTimeService,
        ModelManager $modelManager,
        ModelWrapper $modelWrapper,
        #[GetModel(['id' => 'masterId'])]
        Master $master,
        #[GetModel(['address' => 'address', 'master_id' => 'masterId'])]
        ?Module $module,
        string $name,
        int $address,
        int $typeId,
        bool $withHandshake
    ): AjaxResponse {
        $type = $typeRepository->getById($typeId);

        if ($module !== null) {
            return $this->returnFailure(sprintf(
                'Unter der Adresse %d existiert bereits ein Modul (%s)',
                $address,
                $module->getName()
            ));
        }

        $slave = (new Module($modelWrapper))
            ->setName($name)
            ->setAddress($address)
            ->setMaster($master)
            ->setType($type)
            ->setAdded($dateTimeService->get())
        ;
        $modelManager->save($slave);

        if ($withHandshake) {
            $slaveService = $moduleFactory->get($type->getHelper());
            $slaveService->handshake($slave);
        }

        return $this->returnSuccess($slave);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     */
    #[CheckPermission([Permission::READ])]
    public function get(
        ModuleStore $moduleStore,
        int $limit = 100,
        int $start = 0,
        array $sort = [],
        int $masterId = null
    ): AjaxResponse {
        $moduleStore->setLimit($limit, $start);
        $moduleStore->setSortByExt($sort);
        $moduleStore->setMasterId($masterId);

        return $this->returnSuccess($moduleStore->getList(), $moduleStore->getCount());
    }

    /**
     * @param int[] $ids
     *
     * @throws DeleteError
     */
    #[CheckPermission([Permission::MANAGE, Permission::DELETE])]
    public function delete(ModuleRepository $moduleRepository, array $ids): AjaxResponse
    {
        $moduleRepository->deleteByIds($ids);

        return $this->returnSuccess();
    }
}
