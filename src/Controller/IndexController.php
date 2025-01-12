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
use GibsonOS\Module\Hc\Enum\Direction;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Factory\ModuleFactory;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Store\LogStore;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
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
        array $sort = [],
        int $limit = 100,
        int $start = 0,
    ): AjaxResponse {
        $logStore
            ->setMasterId($masterId)
            ->setModuleId($moduleId)
            ->setDirection($directions === null || $directions === [] || count($directions) !== 1 ? null : reset($directions))
            ->setTypes($types)
            ->setLimit($limit, $start)
            ->setSortByExt($sort)
        ;

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
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     * @throws ClientException
     * @throws RecordException
     */
    #[CheckPermission([Permission::WRITE])]
    public function postLog(
        ModuleFactory $slaveFactory,
        #[GetModel]
        Log $log,
    ): AjaxResponse {
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
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     */
    #[CheckPermission([Permission::READ])]
    public function getLastLog(
        LogRepository $logRepository,
        #[GetModel(['id' => 'masterId'])]
        ?Master $master = null,
        #[GetModel(['id' => 'moduleId'])]
        ?Module $module = null,
        ?int $command = null,
        ?int $type = null,
        ?Direction $direction = null,
    ): AjaxResponse {
        $lastLog = null;

        try {
            if ($master instanceof Master) {
                $lastLog = $logRepository->getLastEntryByMasterId($master->getId() ?? 0, $command, $type, $direction);
            } elseif ($module instanceof Module) {
                $lastLog = $logRepository->getLastEntryByModuleId($module->getId() ?? 0, $command, $type, $direction);
            }
        } catch (SelectError) {
            // Do nothing
        }

        return $this->returnSuccess($lastLog);
    }
}
