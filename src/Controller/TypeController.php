<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Repository\TypeRepository;
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

    /**
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ)]
    public function autoComplete(
        TypeRepository $typeRepository,
        int $id = null,
        string $name = null,
        string $network = null,
        bool $onlyHcSlave = false
    ): AjaxResponse {
        $types = [];

        if ($id > 0) {
            $types = [$typeRepository->getById($id)];
        } elseif (!empty($name)) {
            try {
                $types = $typeRepository->findByName($name, $onlyHcSlave, $network);
            } catch (SelectError) {
                // No type found
            }
        }

        $data = [];

        foreach ($types as $type) {
            $data[] = [
                'id' => $type->getId(),
                'name' => $type->getName(),
                'helper' => $type->getHelper(),
                'network' => $type->getNetwork(),
                'isHcSlave' => $type->getIsHcSlave(),
                'hasInput' => $type->getHasInput(),
            ];
        }

        return $this->returnSuccess($data);
    }
}
