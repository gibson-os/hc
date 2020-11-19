<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use Exception;
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
use GibsonOS\Module\Hc\Exception\Neopixel\ImageExists;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\AnimationService as AnimationAttributeService;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\AnimationService as AnimationSequenceService;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use GibsonOS\Module\Hc\Store\Neopixel\AnimationStore;

class NeopixelAnimationController extends AbstractController
{
    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     * @throws Exception
     */
    public function index(AnimationAttributeService $animationService, ModuleRepository $moduleRepository, int $moduleId): AjaxResponse
    {
        $this->checkPermission(PermissionService::READ);

        $slave = $moduleRepository->getById($moduleId);

        return new AjaxResponse([
            'pid' => $animationService->getPid($slave),
            'started' => $animationService->getStarted($slave),
            'steps' => $animationService->getSteps($slave),
            'transmitted' => $animationService->isTransmitted($slave),
            'success' => true,
            'failure' => false,
        ]);
    }

    /**
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     */
    public function list(AnimationStore $animationStore, int $moduleId): AjaxResponse
    {
        $this->checkPermission(PermissionService::READ);

        $animationStore->setSlave($moduleId);

        return $this->returnSuccess(
            $animationStore->getList(),
            $animationStore->getCount()
        );
    }

    /**
     * @throws DateTimeError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     */
    public function load(AnimationSequenceService $animationService, int $id): AjaxResponse
    {
        $this->checkPermission(PermissionService::READ);

        $steps = $animationService->getById($id);
        $items = [];

        foreach ($steps as $step) {
            foreach ($step as $item) {
                $items[] = $item;
            }
        }

        return $this->returnSuccess($items);
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     * @throws SaveError
     * @throws DeleteError
     */
    public function save(
        AnimationSequenceService $animationService,
        AnimationStore $animationStore,
        ModuleRepository $moduleRepository,
        int $moduleId,
        string $name,
        array $items,
        int $id = null
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        if (!empty($id)) {
            $this->checkPermission(PermissionService::DELETE);
        }

        $slave = $moduleRepository->getById($moduleId);

        if (empty($id)) {
            try {
                $animation = $animationService->getByName($slave, $name);

                new ImageExists(
                    (int) $animation->getId(),
                    sprintf(
                        'Es existiert schon eine Animation unter dem Namen "%s"' . PHP_EOL . 'Möchten Sie es überschreiben?',
                        $name
                    )
                );
            } catch (SelectError $e) {
                // New Animation
            }
        }

        $animation = $animationService->save(
            $slave,
            $name,
            $animationService->transformToTimeSteps($items),
            $id
        );
        $animationStore->setSlave($moduleId);

        return new AjaxResponse([
            'data' => $animationStore->getList(),
            'total' => $animationStore->getCount(),
            'id' => $animation->getId(),
        ]);
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SaveError
     * @throws SelectError
     * @throws AbstractException
     */
    public function send(
        LedService $ledService,
        NeopixelService $neopixelService,
        AnimationSequenceService $animationSequenceService,
        AnimationAttributeService $animationAttributeService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        array $items = []
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        $slave = $moduleRepository->getById($moduleId);
        $steps = $animationSequenceService->transformToTimeSteps($items);
        $runtimes = $animationSequenceService->getRuntimes($steps);
        $msPerStep = 1000 / $slave->getPwmSpeed();
        $newLeds = [];

        $neopixelService->writeSequenceNew($slave);

        foreach ($steps as $time => $leds) {
            $oldLeds = $newLeds;
            $newLeds = [];

            foreach ($leds as $led) {
                $newLeds[$led['led']] = $led;
            }

            $changedLeds = $ledService->getChanges($oldLeds, $newLeds);
            $pwmSteps = (int) ceil($msPerStep * $runtimes[$time]);

            do {
                $runtime = $pwmSteps;

                if ($runtime > 65535) {
                    $pwmSteps -= 65535;
                    $runtime = 65535;
                }

                $neopixelService->writeSequenceAddStep($slave, $runtime, $changedLeds);
                $changedLeds = [];
            } while ($runtime === 65535);
        }

        $animationAttributeService->setSteps($slave, $steps, true);

        return $this->returnSuccess();
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SaveError
     * @throws SelectError
     */
    public function play(
        AnimationSequenceService $animationSequenceService,
        AnimationAttributeService $animationAttributeService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $iterations,
        array $items = []
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        $slave = $moduleRepository->getById($moduleId);
        $steps = $animationSequenceService->transformToTimeSteps($items);
        $animationAttributeService->setSteps($slave, $steps, false);
        $animationSequenceService->play($slave, $iterations);

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
    public function start(
        //AnimationAttributeService $animationService,
        NeopixelService $neopixelService,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        $slave = $moduleRepository->getById($moduleId);
        $neopixelService->writeSequenceStart($slave);
        // @todo refactor. Sollte mit dem locker arbeiten um laufende prozesse zu setzen
        //$animationService->setStarted($slave, true);

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
    public function pause(
        //AnimationAttributeService $animationService,
        NeopixelService $neopixelService,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        $slave = $moduleRepository->getById($moduleId);
        $neopixelService->writeSequencePause($slave);
        //$animationService->setStarted($slave, false);

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
    public function stop(
        //AnimationAttributeService $animationService,
        NeopixelService $neopixelService,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        $slave = $moduleRepository->getById($moduleId);
        $neopixelService->writeSequenceStop($slave);
        //$animationService->setStarted($slave, false);

        return $this->returnSuccess();
    }
}
