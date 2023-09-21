<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use Exception;
use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Dto\Direction;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Store\LogStore;
use JsonException;
use ReflectionException;

class IndexController extends AbstractController
{
    /**
     * @throws SelectError
     * @throws Exception
     */
    #[CheckPermission([Permission::READ])]
    public function getLog(
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
     * @throws AbstractException
     * @throws FactoryError
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     * @throws GetError
     * @throws JsonException
     * @throws ReflectionException
     */
    #[CheckPermission([Permission::WRITE])]
    public function postLog(ModuleFactory $slaveFactory, LogRepository $logRepository, int $id): AjaxResponse
    {
        $log = $logRepository->getById($id);
        $module = $log->getModule();

        if ($module === null) {
            return $this->returnFailure('Senden von Log Einträgen ist nur bei I2C möglich.');
        }

        $slaveService = $slaveFactory->get($module->getType()->getHelper());
        $command = $log->getCommand() ?? 0;
        $data = $log->getRawData();

        if ($log->getDirection() === Direction::INPUT) {
            $slaveService->read($module, $command, strlen($data));
        } else {
            $slaveService->write($module, $command, $data);
        }

        return $this->returnSuccess();
    }

    /**
     * @throws SelectError
     */
    #[CheckPermission([Permission::READ])]
    public function getLastLog(
        LogRepository $logRepository,
        #[GetModel(['id' => 'masterId'])]
        Master $master = null,
        #[GetModel(['id' => 'moduleId'])]
        Module $module = null,
        int $command = null,
        int $type = null,
        Direction $direction = null,
    ) {
        $lastLog = null;

        if ($master !== null) {
            $lastLog = $logRepository->getLastEntryByMasterId($master->getId() ?? 0, $command, $type, $direction);
        } elseif ($module !== null) {
            $lastLog = $logRepository->getLastEntryByModuleId($module->getId() ?? 0, $command, $type, $direction);
        }

        return $this->returnSuccess($lastLog);
    }
}
