<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Model\DeleteError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Dto\Ir\Remote;
use GibsonOS\Module\Hc\Exception\AttributeException;
use GibsonOS\Module\Hc\Exception\IrException;
use GibsonOS\Module\Hc\Formatter\IrFormatter;
use GibsonOS\Module\Hc\Mapper\Ir\RemoteMapper;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
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
     * @throws AttributeException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws DeleteError
     *
     * @return AjaxResponse
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
        try {
            $log = $logRepository->getLastEntryByModuleId($moduleId, AbstractHcSlave::COMMAND_DATA_CHANGED);
            $data = ['lastLogId' => $log->getId() ?? 0];

            if ($lastLogId !== null && $log->getId() !== $lastLogId) {
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
            }
        } catch (SelectError) {
            $data = ['lastLogId' => 0];
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
        ModuleRepository $moduleRepository,
        IrService $irService,
        int $moduleId,
        int $protocol,
        int $address,
        int $command
    ): AjaxResponse {
        $module = $moduleRepository->getById($moduleId);

        $irService->sendKey($module, new Key($protocol, $address, $command));

        return $this->returnSuccess();
    }

    /**
     * @throws AttributeException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     * @throws MapperException
     */
    #[CheckPermission(Permission::READ)]
    public function remote(AttributeRepository $attributeRepository, ?int $remoteId): AjaxResponse
    {
        if ($remoteId !== null) {
            return $this->returnSuccess($attributeRepository->loadDto(new Remote(id: $remoteId)));
        }

        return $this->returnSuccess(new Remote());
    }

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
     * @throws SaveError
     * @throws SelectError
     * @throws AttributeException
     * @throws ReflectionException
     * @throws JsonException
     */
    #[CheckPermission(Permission::WRITE + Permission::MANAGE)]
    public function saveRemote(
        RemoteMapper $remoteMapper,
        AttributeRepository $attributeRepository,
        string $name,
        array $keys,
        string $background = null,
        int $remoteId = null,
        int $moduleId = null
    ): AjaxResponse {
        $attributeRepository->saveDto($remoteMapper->mapRemote($name, $background, $remoteId, $keys));

        return $this->returnSuccess();
    }
}
