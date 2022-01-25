<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Store\TypeStore;

class TypeController extends AbstractController
{
    #[CheckPermission(Permission::READ)]
    public function index(TypeStore $typeStore, int $start = 0, int $limit = 100, array $sort = []): AjaxResponse
    {
        $typeStore->setSortByExt($sort);
        $typeStore->setLimit($limit, $start);

        return $this->returnSuccess($typeStore->getList(), $typeStore->getCount());
    }
}
