<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Protocol;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Server\SendError;

interface ProtocolInterface
{
    public const RECEIVE_LENGTH = 128;

    public function receive(): ?string;

    /**
     * @throws AbstractException
     */
    public function send(int $type, string $data, int $address): void;

    /**
     * @throws SendError
     */
    public function sendReceiveReturn(int $address): void;

    public function receiveReceiveReturn(int $address): void;

    public function receiveReadData(): string;

    public function getName(): string;
}