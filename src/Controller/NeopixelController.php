<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use Exception;
use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModel;
use GibsonOS\Core\Attribute\GetMappedModels;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Core\Utility\StatusCode;
use GibsonOS\Module\Hc\Exception\Neopixel\ImageExists;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Image;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Repository\Neopixel\LedRepository;
use GibsonOS\Module\Hc\Service\Module\NeopixelService;
use GibsonOS\Module\Hc\Service\Neopixel\LedService;
use GibsonOS\Module\Hc\Store\Neopixel\ImageStore;
use GibsonOS\Module\Hc\Store\Neopixel\LedStore;
use JsonException;
use ReflectionException;

class NeopixelController extends AbstractController
{
    /**
     * @throws JsonException
     * @throws SelectError
     * @throws ReflectionException
     */
    #[CheckPermission(Permission::READ)]
    public function index(LedStore $ledStore, #[GetModel(['id' => 'moduleId'])] Module $module): AjaxResponse
    {
        $ledStore->setModule($module);

        $config = JsonUtility::decode($module->getConfig() ?? '');
        $config['pwmSpeed'] = $module->getPwmSpeed();

        return new AjaxResponse(array_merge($config, [
            'success' => true,
            'failure' => false,
            'data' => iterator_to_array($ledStore->getList()),
            'total' => $ledStore->getCount(),
        ]));
    }

    /**
     * @param Led[] $leds
     *
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function showLeds(
        NeopixelService $neopixelService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetMappedModels(Led::class, ['module_id' => 'module.id', 'number' => 'number'])] array $leds = []
    ): AjaxResponse {
        $neopixelService->writeLeds($module, $leds);

        return $this->returnSuccess();
    }

    /**
     * @param Led[] $leds
     *
     * @throws AbstractException
     * @throws DateTimeError
     * @throws DeleteError
     * @throws JsonException
     * @throws SaveError
     * @throws SelectError
     * @throws ReflectionException
     * @throws WriteException
     */
    #[CheckPermission(Permission::MANAGE + Permission::WRITE)]
    public function setLeds(
        NeopixelService $neopixelService,
        LedService $ledService,
        ModelManager $modelManager,
        LedRepository $ledRepository,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetMappedModels(Led::class, ['module_id' => 'module.id', 'number' => 'number'])] array $leds = []
    ): AjaxResponse {
        $ledCounts = $ledService->getChannelCounts($module, $leds);
        $config = JsonUtility::decode($module->getConfig() ?? '[]');
        $ledRepository->startTransaction();

        try {
            if (count(array_diff_assoc($ledCounts, $config['counts']))) {
                $neopixelService->writeLedCounts($module, $ledCounts);

                $config['counts'] = $ledCounts;
                $module->setConfig(JsonUtility::encode($config));
                $modelManager->save($module);
            }

            array_walk(
                $leds,
                function (Led $led) use ($modelManager): void {
                    $modelManager->save($led);
                }
            );

            $ledRepository->deleteWithNumberBiggerAs($module, count($leds) - 1);
        } catch (Exception $exception) {
            $ledRepository->rollback();

            throw $exception;
        }

        $ledRepository->commit();

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     * @throws JsonException
     * @throws ReflectionException
     */
    #[CheckPermission(Permission::WRITE)]
    public function send(
        NeopixelService $neopixelService,
        LedService $ledService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        array $channels = []
    ): AjaxResponse {
        $neopixelService->writeChannels(
            $module,
            array_map(
                fn ($maxId) => $ledService->getNumberById($module, $maxId) + 1,
                $channels
            )
        );

        return $this->returnSuccess();
    }

    /**
     * @throws JsonException
     * @throws SelectError
     * @throws ReflectionException
     */
    #[CheckPermission(Permission::READ)]
    public function images(
        ImageStore $imageStore,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        $imageStore->setModule($module);

        return $this->returnSuccess(
            $imageStore->getList(),
            $imageStore->getCount()
        );
    }

    /**
     * @throws ImageExists
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE, ['id' => Permission::WRITE + Permission::DELETE])]
    public function saveImage(
        ImageStore $imageStore,
        ModelManager $modelManager,
        ?int $id,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetMappedModel(['name' => 'name'], ['module' => 'module'])] Image $image,
    ): AjaxResponse {
        if ($image->getId() !== null && $image->getId() !== $id) {
            throw new ImageExists(
                (int) $image->getId(),
                sprintf(
                    'Es existiert schon ein Bild unter dem Namen "%s".%sMöchten Sie es überschreiben?',
                    $image->getName(),
                    PHP_EOL
                ),
                StatusCode::CONFLICT
            );
        }

        $modelManager->save($image);
        $imageStore->setModule($image->getModule());

        return new AjaxResponse([
            'data' => [...$imageStore->getList()],
            'total' => $imageStore->getCount(),
            'id' => $image->getId(),
            'success' => true,
            'failure' => false,
        ]);
    }
}
