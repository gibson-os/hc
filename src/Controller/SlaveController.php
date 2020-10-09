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
use GibsonOS\Module\Hc\Repository\ModuleRepository;

class SlaveController extends AbstractController
{
    /**
     * @throws SelectError
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     */
    public function autoComplete(
        ModuleRepository $moduleRepository,
        int $id = null,
        string $name = null,
        int $typeId = null
    ): AjaxResponse {
        $this->checkPermission(PermissionService::READ);

        if ($id !== null) {
            $slaves = [$moduleRepository->getById($id)];
        } else {
            try {
                $slaves = $moduleRepository->findByName((string) $name, $typeId);
            } catch (SelectError $e) {
                $slaves = [];
            }
        }

        return $this->returnSuccess($slaves);
    }
}
