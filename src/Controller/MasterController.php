<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\LoginRequired;
use GibsonOS\Core\Exception\PermissionDenied;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\PermissionService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Store\MasterStore;

class MasterController extends AbstractController
{
    /**
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     */
    public function index(MasterStore $masterStore, int $start = 0, int $limit = 100, array $sort = []): AjaxResponse
    {
        $this->checkPermission(PermissionService::READ);

        $masterStore->setLimit($limit, $start);
        $masterStore->setSortByExt($sort);

        return $this->returnSuccess($masterStore->getList(), $masterStore->getCount());
    }

    /**
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SelectError
     */
    public function scanBus(MasterService $masterService, MasterRepository $masterRepository, int $masterId): AjaxResponse
    {
        $this->checkPermission(PermissionService::MANAGE + PermissionService::READ);

        $masterModel = $masterRepository->getById($masterId);
        $masterService->scanBus($masterModel);

        return $this->returnSuccess();
    }
}
