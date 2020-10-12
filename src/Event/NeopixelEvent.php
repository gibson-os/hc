<?php declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Event\Describer\DescriberInterface;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Sequence\ElementRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;

class NeopixelEvent extends AbstractHcEvent
{
    /**
     * @var NeopixelService
     */
    private $neopixelService;

    /**
     * @var ElementRepository
     */
    private $elementRepository;

    public function __construct(
        DescriberInterface $describer,
        TypeRepository $typeRepository,
        NeopixelService $neopixelService,
        ElementRepository $elementRepository
    ) {
        parent::__construct($describer, $typeRepository);
        $this->neopixelService = $neopixelService;
        $this->elementRepository = $elementRepository;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writeSetLeds(Module $slave, array $leds): void
    {
        $this->neopixelService->writeLeds($slave, $leds);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
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
     */
    public function writeSequenceAddStep(Module $slave, int $runtime, array $leds): void
    {
        $this->neopixelService->writeSequenceAddStep($slave, $runtime, $leds);
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
     * @throws SelectError
     */
    public function sendImage(Module $slave, int $imageId): void
    {
        $elements = $this->elementRepository->getBySequence($imageId);
        $element = reset($elements);
        $this->writeSetLeds($slave, JsonUtility::decode($element->getData()));
    }

    public function sendAnimation(Module $slave, int $animationId): void
    {
    }
}
