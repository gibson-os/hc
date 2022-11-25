<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Store\MasterStore;

class MasterController extends AbstractController
{
    /**
     * @throws SelectError
     * @throws \JsonException
     * @throws \ReflectionException
     */
    #[CheckPermission(Permission::READ)]
    public function index(MasterStore $masterStore, int $start = 0, int $limit = 100, array $sort = []): AjaxResponse
    {
        $masterStore->setLimit($limit, $start);
        $masterStore->setSortByExt($sort);

        return $this->returnSuccess($masterStore->getList(), $masterStore->getCount());
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission(Permission::MANAGE + Permission::READ)]
    public function scanBus(MasterService $masterService, #[GetModel(['id' => 'masterId'])] Master $master): AjaxResponse
    {
        $masterService->scanBus($master);

        return $this->returnSuccess();
    }
}
