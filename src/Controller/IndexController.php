<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\LoginRequired;
use GibsonOS\Core\Exception\PermissionDenied;
use GibsonOS\Core\Service\PermissionService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Store\LogStore;

class IndexController extends AbstractController
{
    /**
     * @throws DateTimeError
     * @throws FileNotFound
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     */
    public function log(
        LogStore $logStore,
        ?int $masterId,
        ?int $moduleId,
        ?array $directions,
        ?array $types,
        array $sort = [],
        int $limit = 100,
        int $start = 0
    ): AjaxResponse {
        $this->checkPermission(PermissionService::READ);

        $logStore
            ->setMasterId($masterId)
            ->setModuleId($moduleId)
            ->setDirection(empty($directions) || count($directions) !== 1 ? null : reset($directions))
            ->setTypes($types)
        ;
        $logStore->setLimit($limit, $start);
        $logStore->setSortByExt($sort);

        return new AjaxResponse([
            'success' => true,
            'failure' => false,
            'data' => $logStore->getList(),
            'total' => $logStore->getCount(),
            'traffic' => $logStore->getTraffic(),
        ]);
    }
}
