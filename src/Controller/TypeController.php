<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\LoginRequired;
use GibsonOS\Core\Exception\PermissionDenied;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\PermissionService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Store\TypeStore;

class TypeController extends AbstractController
{
    /**
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     */
    public function index(TypeStore $typeStore, int $start = 0, int $limit = 100, array $sort = []): AjaxResponse
    {
        $this->checkPermission(PermissionService::READ);

        $typeStore->setSortByExt($sort);
        $typeStore->setLimit($limit, $start);

        return $this->returnSuccess($typeStore->getList(), $typeStore->getCount());
    }

    /**
     * @throws DateTimeError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     */
    public function autoComplete(
        TypeRepository $typeRepository,
        int $id = 0,
        string $name = '',
        string $network = null,
        bool $onlyHcSlave = false
    ): AjaxResponse {
        $this->checkPermission(PermissionService::READ);
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
            ];
        }

        return $this->returnSuccess($data);
    }
}
