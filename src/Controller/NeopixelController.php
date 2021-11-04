<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Core\Utility\StatusCode;
use GibsonOS\Module\Hc\Exception\Neopixel\ImageExists;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Mapper\LedMapper;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\ImageService;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use GibsonOS\Module\Hc\Store\Neopixel\ImageStore;
use GibsonOS\Module\Hc\Store\Neopixel\LedStore;
use JsonException;

class NeopixelController extends AbstractController
{
    /**
     * @throws GetError
     * @throws JsonException
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ)]
    public function index(LedStore $ledStore, ModuleRepository $moduleRepository, int $moduleId): AjaxResponse
    {
        $slave = $moduleRepository->getById($moduleId);
        $ledStore->setModuleId($moduleId);

        $config = JsonUtility::decode($slave->getConfig() ?? '');
        $config['pwmSpeed'] = $slave->getPwmSpeed();

        return new AjaxResponse(array_merge($config, [
            'data' => $ledStore->getList(),
            'total' => $ledStore->getCount(),
        ]));
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function showLeds(
        NeopixelService $neopixelService,
        LedMapper $ledMapper,
        ModuleRepository $moduleRepository,
        int $moduleId,
        array $leds = []
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $neopixelService->writeLeds($slave, $ledMapper->mapFromArrays($leds, true, false));

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws DeleteError
     * @throws JsonException
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::MANAGE + Permission::WRITE)]
    public function setLeds(
        NeopixelService $neopixelService,
        LedMapper $ledMapper,
        LedService $ledService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        array $leds = []
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $leds = $ledMapper->mapFromArrays($leds, false, false);
        $ledCounts = $ledService->getChannelCounts($slave, $leds);
        $config = JsonUtility::decode($slave->getConfig() ?? '[]');

        if (count(array_diff_assoc($ledCounts, $config['counts']))) {
            $neopixelService->writeLedCounts($slave, $ledCounts);

            $config['counts'] = $ledCounts;
            $slave->setConfig(JsonUtility::encode($config));
            $slave->save();
        }

        $ledService->saveLeds($slave, $leds);
        $ledService->deleteUnusedLeds($slave, $leds);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission(Permission::WRITE)]
    public function send(
        NeopixelService $neopixelService,
        LedService $ledService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        array $channels = []
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);

        $neopixelService->writeChannels(
            $slave,
            array_map(
                fn ($maxId) => $ledService->getNumberById($slave, $maxId) + 1,
                $channels
            )
        );

        return $this->returnSuccess();
    }

    /**
     * @throws GetError
     */
    #[CheckPermission(Permission::READ)]
    public function images(
        ImageStore $imageStore,
        int $moduleId
    ): AjaxResponse {
        $imageStore->setSlaveId($moduleId);

        return $this->returnSuccess(
            $imageStore->getList(),
            $imageStore->getCount()
        );
    }

    /**
     * @throws DateTimeError
     * @throws DeleteError
     * @throws GetError
     * @throws ImageExists
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE, ['id' => Permission::WRITE + Permission::DELETE])]
    public function saveImage(
        ImageService $imageService,
        ImageStore $imageStore,
        ModuleRepository $moduleRepository,
        LedMapper $ledMapper,
        int $moduleId,
        string $name,
        int $id = null,
        array $leds = []
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);

        if (empty($id)) {
            try {
                $image = $imageService->getByName($slave, $name);

                throw new ImageExists(
                    (int) $image->getId(),
                    sprintf(
                        'Es existiert schon ein Bild unter dem Namen "%s".%sMöchten Sie es überschreiben?',
                        $name,
                        PHP_EOL
                    ),
                    StatusCode::CONFLICT
                );
            } catch (SelectError) {
                // New Image
            }
        }

        $image = $imageService->save($slave, $name, $ledMapper->mapFromArrays($leds, true, false), $id);
        $imageStore->setSlaveId($moduleId);

        return new AjaxResponse([
            'data' => [...$imageStore->getList()],
            'total' => $imageStore->getCount(),
            'id' => $image->getId(),
            'success' => true,
            'failure' => false,
        ]);
    }
}
