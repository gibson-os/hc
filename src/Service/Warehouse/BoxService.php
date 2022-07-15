<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Warehouse;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Service\Module\NeopixelService;

class BoxService
{
    public function __construct(private readonly NeopixelService $neopixelService)
    {
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     */
    public function show(
        Box $box,
        int $red = 255,
        int $green = 255,
        int $blue = 255,
        int $fadeIn = 0,
        int $blink = 0,
    ): void {
        $this->neopixelService->writeLeds(
            $box->getModule(),
            array_map(
                fn (Box\Led $led): Led => $led->getLed()
                    ->setRed($red)
                    ->setGreen($green)
                    ->setBlue($blue)
                    ->setFadeIn($fadeIn)
                    ->setBlink($blink),
                $box->getLeds()
            ),
        );
    }
}
