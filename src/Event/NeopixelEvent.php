<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\ServiceManagerService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\Neopixel\Led;
use GibsonOS\Module\Hc\Event\Describer\NeopixelDescriber;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Mapper\LedMapper;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use Psr\Log\LoggerInterface;

class NeopixelEvent extends AbstractHcEvent
{
    public function __construct(
        NeopixelDescriber $describer,
        ServiceManagerService $serviceManagerService,
        TypeRepository $typeRepository,
        LoggerInterface $logger,
        private NeopixelService $neopixelService,
        private LedMapper $ledMapper
    ) {
        parent::__construct($describer, $serviceManagerService, $typeRepository, $logger);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSetLeds(Module $slave, array $leds): void
    {
        $this->neopixelService->writeLeds($slave, $this->ledMapper->mapFromArrays($leds, true, false));
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function writeChannel(Module $slave, int $channel, int $length = 0): void
    {
        $this->neopixelService->writeChannel($slave, $channel, $length);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSequenceStart(Module $slave, int $repeat = 0): void
    {
        $this->neopixelService->writeSequenceStart($slave, $repeat);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSequenceStop(Module $slave): void
    {
        $this->neopixelService->writeSequenceStop($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSequencePause(Module $slave): void
    {
        $this->neopixelService->writeSequencePause($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSequenceEepromAddress(Module $slave, int $address): void
    {
        $this->neopixelService->writeSequenceEepromAddress($slave, $address);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function readSequenceEepromAddress(Module $slave): int
    {
        return $this->neopixelService->readSequenceEepromAddress($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSequenceNew(Module $slave): void
    {
        $this->neopixelService->writeSequenceNew($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws ReceiveError
     */
    public function readLedCounts(Module $slave): array
    {
        return $this->neopixelService->readLedCounts($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeLedCounts(Module $slave, array $counts): void
    {
        $this->neopixelService->writeLedCounts($slave, $counts);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     */
    public function sendImage(Module $slave, Sequence $sequence): void
    {
        $elements = $sequence->getElements() ?? [];
        $element = reset($elements);
        $this->neopixelService->writeLeds(
            $slave,
            $this->ledMapper->mapFromArrays(JsonUtility::decode($element->getData()), true, false)
        );
    }

    public function sendAnimation(Module $slave, int $animationId): void
    {
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function randomImage(
        Module $slave,
        int $start,
        int $end,
        int $redFrom,
        int $redTo,
        int $greenFrom,
        int $greenTo,
        int $blueFrom,
        int $blueTo
    ): void {
        $leds = [];

        for ($i = $start; $i <= $end; ++$i) {
            $red = mt_rand($redFrom, $redTo);
            $green = mt_rand($greenFrom, $greenTo);
            $blue = mt_rand($blueFrom, $blueTo);
            $this->logger->debug(sprintf('Set LED %d to %d,%d,%d', $i - 1, $red, $green, $blue));
            $leds[$i - 1] = (new Led())
                ->setNumber($i - 1)
                ->setRed($red)
                ->setGreen($green)
                ->setBlue($blue)
                ->setOnlyColor(true)
            ;
        }

        $this->neopixelService->writeLeds($slave, $leds);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     */
    public function sendColor(Module $slave, string $ledRanges, int $red, int $green, int $blue): void
    {
        $leds = [];

        foreach ($this->getLedNumbers($ledRanges) as $ledNumber) {
            $this->logger->debug(sprintf('Set LED %d to %d,%d,%d', $ledNumber, $red, $green, $blue));
            $leds[$ledNumber] = (new Led())
                ->setNumber($ledNumber)
                ->setRed($red)
                ->setGreen($green)
                ->setBlue($blue)
                ->setOnlyColor(true)
            ;
        }

        $this->neopixelService->writeLeds($slave, $leds);
    }

    /**
     * @return array<int, int>
     */
    private function getLedNumbers(string $leds): array
    {
        $this->logger->debug(sprintf('Get LED Numbers from %s', $leds));
        $ledRanges = explode(',', $leds);
        $numbers = [];

        foreach ($ledRanges as $ledRange) {
            $ledRange = explode('-', $ledRange);

            if (count($ledRange) === 1) {
                $ledRange[1] = $ledRange[0];
            }

            for ($i = (int) $ledRange[0]; $i <= (int) $ledRange[1]; ++$i) {
                $number = $i - 1;
                $numbers[$number] = $number;
            }
        }

        ksort($numbers);

        return $numbers;
    }
}
