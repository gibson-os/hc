<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Store\MasterStore;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class MasterController extends AbstractController
{
    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     * @throws ClientException
     * @throws RecordException
     */
    #[CheckPermission([Permission::READ])]
    public function get(MasterStore $masterStore, int $start = 0, int $limit = 100, array $sort = []): AjaxResponse
    {
        $masterStore->setLimit($limit, $start);
        $masterStore->setSortByExt($sort);

        return $this->returnSuccess($masterStore->getList(), $masterStore->getCount());
    }

    /**
     * @throws AbstractException
     * @throws SelectError
     */
    #[CheckPermission([Permission::MANAGE, Permission::READ])]
    public function getScanBus(MasterService $masterService, #[GetModel(['id' => 'masterId'])] Master $master): AjaxResponse
    {
        $masterService->scanBus($master);

        return $this->returnSuccess();
    }
}
