<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Io;

use GibsonOS\Module\Hc\Model\Io\DirectConnect as DirectConnectModel;
use JsonSerializable;

class DirectConnect implements JsonSerializable
{
    public function __construct(
        private readonly DirectConnectModel $directConnect,
        private readonly bool $more,
    ) {
    }

    public function getDirectConnect(): DirectConnectModel
    {
        return $this->directConnect;
    }

    public function hasMore(): bool
    {
        return $this->more;
    }

    public function jsonSerialize(): array
    {
        return [
            'directConnect' => $this->getDirectConnect(),
            'hasMore' => $this->hasMore(),
        ];
    }
}
