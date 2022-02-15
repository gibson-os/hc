<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Attribute\GetObject;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\EventException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Model\DeleteError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\Event;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Dto\Ir\Remote;
use GibsonOS\Module\Hc\Exception\AttributeException;
use GibsonOS\Module\Hc\Exception\IrException;
use GibsonOS\Module\Hc\Formatter\IrFormatter;
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
     * @throws SelectError
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
     * @throws AttributeException
     */
    #[CheckPermission(Permission::MANAGE + Permission::WRITE)]
    public function addKey(
        AttributeRepository $attributeRepository,
        string $name,
        int $protocol,
        int $address,
        int $command
    ): AjaxResponse {
        $attributeRepository->saveDto(new Key($protocol, $address, $command, $name));

        return $this->returnSuccess();
    }

    #[CheckPermission(Permission::MANAGE + Permission::DELETE)]
    public function deleteKeys(
        AttributeRepository $attributeRepository,
        IrFormatter $irFormatter,
        array $keys
    ): AjaxResponse {
        $attributeRepository->deleteSubIds(array_map(
            static fn (array $key) => $irFormatter->getSubId($key['protocol'], $key['address'], $key['command']),
            $keys
        ));

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

        for ($retry = 0; $retry < 10000; ++$retry) {
            try {
                $log = $logRepository->getLastEntryByModuleId($moduleId, AbstractHcSlave::COMMAND_DATA_CHANGED);
                $data = ['lastLogId' => $log->getId() ?? 0];

                if ($lastLogId === null || $log->getId() === $lastLogId) {
                    $lastLogId = $log->getId();

                    continue;
                }

                $data['key'] = $irFormatter->getKeys($log->getRawData())[0];
                $name = $data['key']->getName();

                if ($name !== null) {
                    $exception = new IrException(sprintf(
                        'Taste is bereits unter dem Namen "%s" vorhanden!',
                        $name
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
     */
    #[CheckPermission(Permission::WRITE)]
    public function send(
        IrService $irService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        int $protocol,
        int $address,
        int $command
    ): AjaxResponse {
        $irService->sendKeys($module, [new Key($protocol, $address, $command)]);

        return $this->returnSuccess();
    }

    /**
     * @throws AttributeException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     * @throws MapperException
     * @throws FactoryError
     */
    #[CheckPermission(Permission::READ)]
    public function remote(AttributeRepository $attributeRepository, ?int $remoteId): AjaxResponse
    {
        if ($remoteId !== null) {
            return $this->returnSuccess($attributeRepository->loadDto(new Remote(id: $remoteId)));
        }

        return $this->returnSuccess(new Remote());
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
     * @throws AttributeException
     * @throws DeleteError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws FactoryError
     */
    #[CheckPermission(Permission::WRITE + Permission::MANAGE)]
    public function saveRemote(
        AttributeRepository $attributeRepository,
        #[GetObject(['id' => 'remoteId'])] Remote $remote,
        int $moduleId = null
    ): AjaxResponse {
        $attributeRepository->saveDto($remote);

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
     */
    #[CheckPermission(Permission::WRITE)]
    public function sendRemoteKey(
        EventService $eventService,
        IrService $irService,
        IrFormatter $irFormatter,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetModel(['id' => 'eventId'])] ?Event $event,
        array $keys = []
    ): AjaxResponse {
        if ($event !== null) {
            $eventService->runEvent($event, true);
        }

        $irService->sendKeys(
            $module,
            array_map(
                fn (int $key): Key => $irFormatter->getKeyBySubId($key),
                $keys
            )
        );

        return $this->returnSuccess();
    }
}
