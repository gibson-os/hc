<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModel;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Attribute\GetModels;
use GibsonOS\Core\Attribute\GetObject;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\EventException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\DeleteError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Attribute\GetAttribute;
use GibsonOS\Module\Hc\Exception\IrException;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Formatter\IrFormatter;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Ir\Remote;
use GibsonOS\Module\Hc\Model\Ir\Remote\Button;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;
use GibsonOS\Module\Hc\Service\Slave\IrService;
use GibsonOS\Module\Hc\Store\Ir\KeyStore;
use GibsonOS\Module\Hc\Store\Ir\RemoteStore;
use JsonException;
use ReflectionException;

class IrController extends AbstractController
{
    /**
     * @param KeyStore $keyStore
     * @param int      $limit
     * @param int      $start
     * @param array    $sort
     *
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     *
     * @return AjaxResponse
     */
    #[CheckPermission(Permission::READ)]
    public function keys(
        KeyStore $keyStore,
        int $limit = 100,
        int $start = 0,
        array $sort = [['property' => 'name', 'direction' => 'ASC']]
    ): AjaxResponse {
        $keyStore->setLimit($limit, $start);
        $keyStore->setSortByExt($sort);

        return $this->returnSuccess(
            $keyStore->getList(),
            $keyStore->getCount()
        );
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws DeleteError
     * @throws FactoryError
     */
    #[CheckPermission(Permission::MANAGE + Permission::WRITE)]
    public function addKey(
        ModelManager $modelManager,
        #[GetMappedModel] Key $key,
    ): AjaxResponse {
        $modelManager->save($key);

        return $this->returnSuccess();
    }

    /**
     * @param Key[] $keys
     */
    #[CheckPermission(Permission::MANAGE + Permission::DELETE)]
    public function deleteKeys(
        ModelManager $modelManager,
        #[GetModels(Key::class)] array $keys,
    ): AjaxResponse {
        foreach ($keys as $key) {
            $modelManager->delete($key);
        }

        return $this->returnSuccess();
    }

    /**
     * @throws IrException
     */
    #[CheckPermission(Permission::READ)]
    public function waitForKey(
        LogRepository $logRepository,
        IrFormatter $irFormatter,
        int $moduleId,
        int $lastLogId = null
    ): AjaxResponse {
        $data = ['lastLogId' => 0];

        for ($retry = 0; $retry < 20000; ++$retry) {
            try {
                $log = $logRepository->getLastEntryByModuleId($moduleId, AbstractHcSlave::COMMAND_DATA_CHANGED);
                $data = ['lastLogId' => $log->getId() ?? 0];

                if ($lastLogId === null || $log->getId() === $lastLogId) {
                    $lastLogId = $log->getId();
                    usleep(10);

                    continue;
                }

                $key = $irFormatter->getKeys($log->getRawData())[0];
                $data['key'] = $key;

                if ($key->getId() !== null) {
                    $exception = new IrException(sprintf(
                        'Taste ist bereits unter dem Namen "%s" vorhanden!',
                        $key->getName()
                    ));
                    $exception->setType(AbstractException::INFO);

                    throw $exception;
                }

                break;
            } catch (SelectError) {
            }

            usleep(10);
        }

        return $this->returnSuccess($data);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission(Permission::WRITE)]
    public function send(
        IrService $irService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetObject] Key $key
    ): AjaxResponse {
        $irService->sendKeys($module, [$key]);

        return $this->returnSuccess();
    }

    #[CheckPermission(Permission::READ)]
    public function remote(#[GetAttribute(['id' => 'remoteId'])] Remote $remote): AjaxResponse
    {
        return $this->returnSuccess($remote);
    }

    /**
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ)]
    public function remotes(
        RemoteStore $remoteStore,
        int $limit = 100,
        int $start = 0,
        array $sort = [['property' => 'name', 'direction' => 'ASC']]
    ): AjaxResponse {
        $remoteStore->setLimit($limit, $start);
        $remoteStore->setSortByExt($sort);

        return $this->returnSuccess(
            $remoteStore->getList(),
            $remoteStore->getCount()
        );
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    #[CheckPermission(Permission::WRITE + Permission::MANAGE)]
    public function saveRemote(
        ModelManager $modelManager,
        #[GetMappedModel] Remote $remote,
    ): AjaxResponse {
        $modelManager->save($remote);

        return $this->returnSuccess();
    }

    #[CheckPermission(Permission::DELETE + Permission::MANAGE)]
    public function deleteRemotes(AttributeRepository $attributeRepository, array $remoteIds): AjaxResponse
    {
        $attributeRepository->deleteSubIds($remoteIds, Remote::class);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws JsonException
     * @throws SaveError
     * @throws SelectError
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws ReflectionException
     * @throws WriteException
     */
    #[CheckPermission(Permission::WRITE)]
    public function sendButton(
        EventService $eventService,
        IrService $irService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetModel] Button $button,
    ): AjaxResponse {
        $event = $button->getEvent();

        if ($event !== null) {
            $eventService->runEvent($event, true);
        }

        $irService->sendKeys($module, array_map(fn (Remote\Key $key): Key => $key->getKey(), $button->getKeys()));

        return $this->returnSuccess();
    }
}
