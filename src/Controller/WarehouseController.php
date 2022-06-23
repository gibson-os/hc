<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Store\Warehouse\BoxStore;
use JsonException;
use ReflectionException;

class WarehouseController extends AbstractController
{
    /**
     * @throws SelectError
     * @throws JsonException
     * @throws ReflectionException
     */
    #[CheckPermission(Permission::READ)]
    public function index(
        BoxStore $boxStore,
        #[GetModel(['id' => 'moduleId'])] Module $module,
    ): AjaxResponse {
        $boxStore->setModule($module);

        return $this->returnSuccess($boxStore->getList(), $boxStore->getCount());
    }
}
