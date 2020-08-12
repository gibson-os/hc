<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\LoginRequired;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\PermissionDenied;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\PermissionService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Exception\Neopixel\ImageExists;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\ImageService;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use GibsonOS\Module\Hc\Store\Neopixel\ImageStore;
use GibsonOS\Module\Hc\Store\Neopixel\LedStore;

class NeopixelController extends AbstractController
{
    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     */
    public function index(LedStore $ledStore, ModuleRepository $moduleRepository, int $moduleId): AjaxResponse
    {
        $this->checkPermission(PermissionService::READ);

        $slave = $moduleRepository->getById($moduleId);
        $ledStore->setModule($moduleId);

        $config = JsonUtility::decode($slave->getConfig() ?? '');
        $config['pwmSpeed'] = $slave->getPwmSpeed();

        return new AjaxResponse(array_merge($config, [
            'data' => $ledStore->getList(),
            'total' => $ledStore->getCount(),
        ]));
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     * @throws AbstractException
     * @throws SaveError
     */
    public function saveLeds(
        NeopixelService $neopixelService,
        LedService $ledService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        array $leds = []
    ): AjaxResponse {
        $this->checkPermission(PermissionService::MANAGE + PermissionService::WRITE);

        $slave = $moduleRepository->getById($moduleId);
        $ledCounts = $ledService->getChannelCounts($slave, $leds);
        $config = JsonUtility::decode($slave->getConfig() ?? '[]');

        if (count(array_diff_assoc($ledCounts, $config['counts']))) {
            $neopixelService->writeLedCounts($slave, $ledCounts);

            $config['counts'] = $ledCounts;
            $slave->setConfig(JsonUtility::encode($config));
            $slave->save();
        }

        $this->writeLeds($neopixelService, $ledService, $slave, $leds);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws DeleteError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SaveError
     * @throws SelectError
     */
    public function setLeds(
        NeopixelService $neopixelService,
        LedService $ledService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        array $leds = []
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        foreach ($leds as $id => $led) {
            unset(
                $led[LedService::ATTRIBUTE_KEY_TOP],
                $led[LedService::ATTRIBUTE_KEY_LEFT],
                $led[LedService::ATTRIBUTE_KEY_CHANNEL
            ]);

            $leds[$id] = $led;
        }

        $this->writeLeds($neopixelService, $ledService, $moduleRepository->getById($moduleId), $leds);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SaveError
     * @throws SelectError
     */
    public function send(
        NeopixelService $neopixelService,
        LedService $ledService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        array $channels = []
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        $slave = $moduleRepository->getById($moduleId);

        foreach ($channels as $channel => $maxId) {
            $neopixelService->writeChannel(
                $slave,
                $channel,
                $ledService->getNumberById($slave, $maxId) + 1
            );
        }

        return $this->returnSuccess();
    }

    /**
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     */
    public function images(
        ImageStore $imageStore,
        int $moduleId
    ): AjaxResponse {
        $this->checkPermission(PermissionService::READ);

        $imageStore->setSlave($moduleId);

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
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SaveError
     * @throws SelectError
     */
    public function saveImage(
        ImageService $imageService,
        ImageStore $imageStore,
        ModuleRepository $moduleRepository,
        int $moduleId,
        string $name,
        int $id = null,
        array $leds = []
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        if (!empty($id)) {
            $this->checkPermission(PermissionService::DELETE);
        }

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
                    )
                );
            } catch (SelectError $e) {
                // New Image
            }
        }

        $image = $imageService->save($slave, $name, $leds, $id);
        $imageStore->setSlave($moduleId);

        return new AjaxResponse([
            'data' => $imageStore->getList(),
            'total' => $imageStore->getCount(),
            'id' => $image->getId(),
        ]);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws GetError
     * @throws SaveError
     * @throws SelectError
     * @throws DeleteError
     */
    private function writeLeds(NeopixelService $neopixelService, LedService $ledService, Module $slave, array $leds): void
    {
        $changedLeds = $ledService->getChanges($ledService->getActualState($slave), $leds);
        $changedSlaveLeds = $ledService->getChangedLedsWithoutIgnoredAttributes($changedLeds);
        $neopixelService->writeSetLeds($slave, array_intersect_key($leds, $changedSlaveLeds));
        $ledService->saveLeds($slave, $leds);
        $ledService->deleteUnusedLeds($slave, $leds);
        $lastChangedIds = $ledService->getLastIds($slave, $changedSlaveLeds);

        if (empty($lastChangedIds)) {
            $startCount = 0;
            $lastChangedIds = array_map(function ($count) use (&$startCount) {
                if ($count === 0) {
                    return -1;
                }

                $startCount += $count;

                return $startCount - 1;
            }, JsonUtility::decode($slave->getConfig() ?: JsonUtility::encode(['counts' => 0]))['counts']);
        }

        foreach ($lastChangedIds as $channel => $lastChangedId) {
            if ($lastChangedId < 1) {
                continue;
            }

            $neopixelService->writeChannel(
                $slave,
                $channel,
                $ledService->getNumberById($slave, $lastChangedId) + 1
            );
        }
    }
}
