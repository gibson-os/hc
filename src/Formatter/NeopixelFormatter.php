<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Module\Hc\Mapper\NeopixelMapper;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use GibsonOS\Module\Hc\Service\TransformService;

class NeopixelFormatter extends AbstractHcFormatter
{
    private NeopixelService $neopixelService;

    private NeopixelMapper $neopixelMapper;

    public function __construct(
        TransformService $transform,
        NeopixelService $neopixelService,
        NeopixelMapper $neopixelMapper
    ) {
        parent::__construct($transform);
        $this->neopixelService = $neopixelService;
        $this->neopixelMapper = $neopixelMapper;
    }

    public function render(Log $log): ?string
    {
        $slave = $log->getModule();

        return parent::render($log);
    }
}
