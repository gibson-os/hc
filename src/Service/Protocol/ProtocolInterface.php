<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Protocol;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Server\SendError;
use GibsonOS\Module\Hc\Dto\BusMessage;

interface ProtocolInterface
{
    public const RECEIVE_LENGTH = 128;

    public function receive(): ?BusMessage;

    /**
     * @throws AbstractException
     */
    public function send(BusMessage $busMessage): void;

    /**
     * @throws SendError
     */
    public function sendReceiveReturn(BusMessage $busMessage): void;

    public function receiveReceiveReturn(BusMessage $busMessage): void;

    public function receiveReadData(?int $port): BusMessage;

    public function getName(): string;
}
