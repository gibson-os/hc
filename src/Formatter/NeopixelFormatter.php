<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Module\Hc\Mapper\NeopixelMapper;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\Module\Hc\Store\Neopixel\LedStore;

class NeopixelFormatter extends AbstractHcFormatter
{
    private LedStore $ledStore;

    private NeopixelMapper $neopixelMapper;

    private array $leds = [];

    public function __construct(
        TransformService $transform,
        LedStore $ledStore,
        NeopixelMapper $neopixelMapper
    ) {
        parent::__construct($transform);
        $this->ledStore = $ledStore;
        $this->neopixelMapper = $neopixelMapper;
    }

    public function render(Log $log): ?string
    {
        return parent::render($log);
    }

    private function getLeds(int $moduleId): array
    {
        if (!isset($this->leds[$moduleId])) {
            $this->ledStore->setModule($moduleId);
            $this->leds[$moduleId] = $this->ledStore->getList();
        }

        return $this->leds[$moduleId];
    }
}
