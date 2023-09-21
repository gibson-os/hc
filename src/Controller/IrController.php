<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModel;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Attribute\GetModels;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\HttpStatusCode;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\EventException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\DeleteError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Service\Response\ExceptionResponse;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Exception\IrException;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Formatter\IrFormatter;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Ir\Key\Name;
use GibsonOS\Module\Hc\Model\Ir\Remote;
use GibsonOS\Module\Hc\Model\Ir\Remote\Button;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\Module\AbstractHcModule;
use GibsonOS\Module\Hc\Service\Module\IrService;
use GibsonOS\Module\Hc\Store\Ir\KeyStore;
use GibsonOS\Module\Hc\Store\Ir\RemoteStore;
use JsonException;
use ReflectionException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class IrController extends AbstractController
{
    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     */
    #[CheckPermission([Permission::READ])]
    public function getKeys(
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
     */
    #[CheckPermission([Permission::MANAGE, Permission::WRITE])]
    public function postKey(
        ModelManager $modelManager,
        #[GetMappedModel]
        Key $key,
        string $name,
    ): AjaxResponse {
        $key->unloadNames();
        $key->addNames([(new Name())->setName($name)]);
        $modelManager->save($key);

        return $this->returnSuccess();
    }

    /**
     * @param Key[] $keys
     *
     * @throws DeleteError
     * @throws JsonException
     */
    #[CheckPermission([Permission::MANAGE, Permission::DELETE])]
    public function deleteKeys(
        ModelManager $modelManager,
        #[GetModels(Key::class)]
        array $keys,
    ): AjaxResponse {
        foreach ($keys as $key) {
            $modelManager->delete($key);
        }

        return $this->returnSuccess();
    }

    /**
     * @throws JsonException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[CheckPermission([Permission::READ])]
    public function getWaitForKey(
        LogRepository $logRepository,
        IrFormatter $irFormatter,
        int $moduleId,
        int $lastLogId = null
    ): AjaxResponse {
        $data = ['lastLogId' => 0];

        for ($retry = 0; $retry < 20000; ++$retry) {
            try {
                $log = $logRepository->getLastEntryByModuleId($moduleId, AbstractHcModule::COMMAND_DATA_CHANGED);
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
                        'Taste ist bereits unter den Namen "%s" vorhanden. Trotzdem speichern?',
                        implode('", "', array_map(
                            static fn (Name $name): string => $name->getName(),
                            $key->getNames()
                        ))
                    ));
                    $exception->setType(AbstractException::QUESTION);
                    $exception->setTitle('Taste bereits vorhanden');
                    $exception->addButton('Ja', value: true);
                    $exception->addButton('Nein', value: false);

                    $response = new ExceptionResponse(
                        $exception,
                        $this->requestService,
                        $this->twigService,
                    );

                    $body = JsonUtility::decode($response->getBody());
                    $body['data'] = array_merge($body['data'], $data);

                    return new AjaxResponse($body, HttpStatusCode::CONFLICT);
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
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[CheckPermission([Permission::WRITE])]
    public function post(
        IrService $irService,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
        #[GetModel]
        Key $key
    ): AjaxResponse {
        $irService->sendKeys($module, [$key]);

        return $this->returnSuccess();
    }

    #[CheckPermission([Permission::READ])]
    public function getRemote(#[GetModel] Remote $remote): AjaxResponse
    {
        return $this->returnSuccess($remote);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     */
    #[CheckPermission([Permission::READ])]
    public function getRemotes(
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
    #[CheckPermission([Permission::WRITE, Permission::MANAGE])]
    public function postRemote(
        ModelManager $modelManager,
        #[GetMappedModel]
        Remote $remote,
    ): AjaxResponse {
        $modelManager->save($remote);

        return $this->returnSuccess();
    }

    /**
     * @param Remote[] $remotes
     *
     * @throws DeleteError
     * @throws JsonException
     */
    #[CheckPermission([Permission::DELETE, Permission::MANAGE])]
    public function deleteRemotes(
        ModelManager $modelManager,
        #[GetModels(Remote::class)]
        array $remotes
    ): AjaxResponse {
        foreach ($remotes as $remote) {
            $modelManager->delete($remote);
        }

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
    #[CheckPermission([Permission::WRITE])]
    public function postButton(
        EventService $eventService,
        IrService $irService,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
        #[GetModel]
        Button $button,
    ): AjaxResponse {
        $event = $button->getEvent();

        if ($event !== null) {
            $eventService->runEvent($event, true);
        }

        $irService->sendKeys($module, array_map(fn (Remote\Key $key): Key => $key->getKey(), $button->getKeys()));

        return $this->returnSuccess();
    }
}
