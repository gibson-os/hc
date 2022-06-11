<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Io;

use GibsonOS\Module\Hc\Model\Io\DirectConnect as DirectConnectModel;

class DirectConnect
{
    public function __construct(
        private readonly DirectConnectModel $directConnect,
        private readonly bool $hasMore,
    ) {
    }

    public function getDirectConnect(): DirectConnectModel
    {
        return $this->directConnect;
    }

    public function isHasMore(): bool
    {
        return $this->hasMore;
    }
}
