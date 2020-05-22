<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Service\PermissionService;
use GibsonOS\Core\Service\Response\ResponseInterface;
use GibsonOS\Module\Hc\Store\EventStore;

class EventController extends AbstractController
{
    public function index(
        EventStore $eventStore,
        ?int $masterId,
        ?int $slaveId,
        int $start = 0,
        int $limit = 0,
        array $sort = []
    ): ResponseInterface {
        $this->checkPermission(PermissionService::READ);

        $eventStore
            ->setMasterId($masterId)
            ->setSlaveId($slaveId)
        ;
        $eventStore->setLimit($limit, $start);
        $eventStore->setSortByExt($sort);

        return $this->returnSuccess($eventStore->getList(), $eventStore->getCount());
    }
}
