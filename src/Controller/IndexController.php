<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Factory\SlaveFactory;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Store\LogStore;

class IndexController extends AbstractController
{
    /**
     * @throws DateTimeError
     * @throws GetError
     */
    #[CheckPermission(Permission::READ)]
    public function log(
        LogStore $logStore,
        ?int $masterId,
        ?int $moduleId,
        ?array $directions,
        array $types = [],
        array $sort = [['property' => 'added', 'direction' => 'DESC']],
        int $limit = 100,
        int $start = 0
    ): AjaxResponse {
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

    /**
     * @throws DateTimeError
     * @throws AbstractException
     * @throws FactoryError
     * @throws SaveError
     * @throws SelectError
     * @throws ReceiveError
     */
    #[CheckPermission(Permission::WRITE)]
    public function logSend(SlaveFactory $slaveFactory, LogRepository $logRepository, int $id): AjaxResponse
    {
        $log = $logRepository->getById($id);
        $module = $log->getModule();

        if ($module === null) {
            return $this->returnFailure('Senden von Log Einträgen ist nur bei I2C möglich.');
        }

        $slaveService = $slaveFactory->get($module->getType()->getHelper());
        $command = $log->getCommand() ?? 0;
        $data = $log->getRawData();

        if ($log->getDirection() === Log::DIRECTION_INPUT) {
            $slaveService->read($module, $command, strlen($data));
        } else {
            $slaveService->write($module, $command, $data);
        }

        return $this->returnSuccess();
    }
}
