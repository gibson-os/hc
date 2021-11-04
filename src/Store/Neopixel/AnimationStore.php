<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Neopixel;

use GibsonOS\Module\Hc\Service\Sequence\Neopixel\AnimationService as AnimationService;
use GibsonOS\Module\Hc\Store\AbstractSequenceStore;

class AnimationStore extends AbstractSequenceStore
{
    protected function getDefaultOrder(): string
    {
        return '`name`';
    }

    protected function getType(): int
    {
        return AnimationService::SEQUENCE_TYPE;
    }

    protected function loadElements(): bool
    {
        return false;
    }
}
