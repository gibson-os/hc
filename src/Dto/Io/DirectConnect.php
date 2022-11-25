<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Io;

use GibsonOS\Module\Hc\Model\Io\DirectConnect as DirectConnectModel;
use GibsonOS\Module\Hc\Model\Io\Port;

class DirectConnect implements \JsonSerializable
{
    public function __construct(
        private readonly Port $inputPort,
        private readonly bool $more,
        private readonly ?DirectConnectModel $directConnect = null,
    ) {
    }

    public function getInputPort(): Port
    {
        return $this->inputPort;
    }

    public function hasMore(): bool
    {
        return $this->more;
    }

    public function getDirectConnect(): ?DirectConnectModel
    {
        return $this->directConnect;
    }

    public function jsonSerialize(): array
    {
        return [
            'inputPort' => $this->getInputPort(),
            'hasMore' => $this->hasMore(),
            'directConnect' => $this->getDirectConnect(),
        ];
    }
}
