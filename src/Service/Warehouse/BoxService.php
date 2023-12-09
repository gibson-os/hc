<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Warehouse;

use Exception;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Repository\Warehouse\BoxRepository;
use GibsonOS\Module\Hc\Service\Module\NeopixelService;
use JsonException;
use MDO\Client;
use ReflectionException;

class BoxService
{
    public function __construct(
        private readonly NeopixelService $neopixelService,
        private readonly ModelManager $modelManager,
        private readonly Client $client,
        private readonly BoxRepository $boxRepository,
    ) {
    }

    /**
     * @param Box[] $boxes
     *
     * @throws AbstractException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function showLeds(
        array $boxes,
        int $red = 255,
        int $green = 255,
        int $blue = 255,
        int $fadeIn = 0,
        int $blink = 0,
    ): void {
        $this->client->startTransaction();

        $ledsByModules = [];

        foreach ($boxes as $box) {
            $module = $box->getModule();
            $moduleId = $module->getId() ?? 0;
            $leds = $ledsByModules[$moduleId]['leds'] ?? [];
            $boxes = $ledsByModules[$moduleId]['boxes'] ?? [];
            $boxes[$box->getId() ?? 0] = $box;

            foreach ($box->getLeds() as $led) {
                $leds[] = $led->getLed()
                    ->setRed($red)
                    ->setGreen($green)
                    ->setBlue($blue)
                    ->setFadeIn($fadeIn)
                    ->setBlink($blink)
                ;
            }

            $ledsByModules[$moduleId] = [
                'module' => $module,
                'leds' => $leds,
                'boxes' => $boxes,
            ];

            try {
                $this->modelManager->save($box->setShown(true));
            } catch (Exception $exception) {
                $this->client->rollback();

                throw $exception;
            }
        }

        foreach ($ledsByModules as $ledsByModule) {
            /** @var Module $module */
            $module = $ledsByModule['module'];
            $this->disableLeds($module, $ledsByModules);

            if (count($ledsByModule['leds']) === 0) {
                continue;
            }

            try {
                $this->neopixelService->writeLeds($module, $ledsByModule['leds']);
            } catch (Exception $exception) {
                $this->client->rollback();

                throw $exception;
            }
        }

        $this->client->commit();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws DateTimeError
     * @throws SelectError
     */
    private function disableLeds(Module $module, array $ledsByModules): void
    {
        $leds = [];

        foreach ($this->boxRepository->getByModule($module) as $box) {
            if (isset($ledsByModules['boxes'][$box->getId() ?? 0])) {
                continue;
            }

            foreach ($box->getLeds() as $led) {
                $leds[] = $led->getLed()
                    ->setRed(0)
                    ->setGreen(0)
                    ->setBlue(0)
                    ->setFadeIn(0)
                    ->setBlink(0)
                ;
            }
        }

        $this->neopixelService->writeLeds($module, $leds);
    }
}
