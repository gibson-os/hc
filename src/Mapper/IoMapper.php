<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper;

use GibsonOS\Module\Hc\Service\Slave\IoService;
use GibsonOS\Module\Hc\Service\TransformService;

class IoMapper
{
    private TransformService $transformService;

    public function __construct(TransformService $transformService)
    {
        $this->transformService = $transformService;
    }

    public function getPortAsArray(string $data): array
    {
        $byte1 = $this->transformService->asciiToUnsignedInt($data, 0);
        $byte2 = $this->transformService->asciiToUnsignedInt($data, 1);

        $port = [
            IoService::ATTRIBUTE_PORT_KEY_DIRECTION => $byte1 & 1,
            IoService::ATTRIBUTE_PORT_KEY_VALUE => $byte1 >> 2 & 1,
        ];

        if ($port[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] == IoService::DIRECTION_INPUT) {
            $port[IoService::ATTRIBUTE_PORT_KEY_DELAY] = ($byte1 >> 3) | $byte2;
            $port[IoService::ATTRIBUTE_PORT_KEY_PULL_UP] = $byte1 >> 1 & 1;
        } else {
            $port[IoService::ATTRIBUTE_PORT_KEY_PWM] = $byte2;
            $port[IoService::ATTRIBUTE_PORT_KEY_FADE_IN] = 0;

            if ($port[IoService::ATTRIBUTE_PORT_KEY_VALUE]) {
                $port[IoService::ATTRIBUTE_PORT_KEY_FADE_IN] = $port[IoService::ATTRIBUTE_PORT_KEY_PWM];
                $port[IoService::ATTRIBUTE_PORT_KEY_PWM] = 0;
            }

            $port[IoService::ATTRIBUTE_PORT_KEY_BLINK] = $byte1 >> 3;
        }

        return $port;
    }

    public function getPortsAsArray(string $data, int $portCount): array
    {
        $byteCount = 0;
        $ports = [];

        for ($i = 0; $i < $portCount; ++$i) {
            $ports[$i] = $this->getPortAsArray(substr($data, $byteCount, 2));
            $byteCount += 2;
        }

        return $ports;
    }

    public function getPortAsString(array $data): string
    {
        if ($data[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] == IoService::DIRECTION_INPUT) {
            return
                chr(
                    $data[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] |
                    ($data[IoService::ATTRIBUTE_PORT_KEY_PULL_UP] << 1) |
                    ($data[IoService::ATTRIBUTE_PORT_KEY_VALUE] << 2) |
                    ($data[IoService::ATTRIBUTE_PORT_KEY_DELAY] << 3)
                ) .
                chr($data[IoService::ATTRIBUTE_PORT_KEY_DELAY]);
        }
        $pwm = $data[IoService::ATTRIBUTE_PORT_KEY_PWM];

        if ($data[IoService::ATTRIBUTE_PORT_KEY_VALUE]) {
            $pwm = $data[IoService::ATTRIBUTE_PORT_KEY_FADE_IN];
        }

        return
            chr(
                $data[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] |
                ($data[IoService::ATTRIBUTE_PORT_KEY_PULL_UP] << 1) |
                ($data[IoService::ATTRIBUTE_PORT_KEY_VALUE] << 2) |
                ($data[IoService::ATTRIBUTE_PORT_KEY_BLINK] << 3)
            ) .
            chr((int) $pwm)
            ;
    }

    public function getDirectConnectAsArray(string $data): array
    {
        $inputValueAndOutputPortByte = $this->transformService->asciiToUnsignedInt($data, 0);
        $setByte = $this->transformService->asciiToUnsignedInt($data, 1);
        $pwmByte = $this->transformService->asciiToUnsignedInt($data, 2);
        $addOrSub = 0;

        if ($setByte & 1) {
            $addOrSub = 1;
        } elseif (($pwmByte >> 1) & 1) {
            $addOrSub = -1;
        }

        $value = (($setByte >> 2) & 1);

        return [
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE => ($inputValueAndOutputPortByte >> 7),
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT => ($inputValueAndOutputPortByte & 127),
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE => $value,
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM => $value ? 0 : $pwmByte,
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK => (($setByte >> 3) & 7),
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN => $value ? $pwmByte : 0,
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB => $addOrSub,
        ];
    }

    public function getDirectConnectAsString(
        int $inputPort,
        int $inputValue,
        int $outputPort,
        int $value,
        int $pwm,
        int $blink,
        int $fadeIn,
        int $addOrSub,
        int $order = null
    ): string {
        $return = chr($inputPort);

        if (null !== $order) {
            $return .= chr($order);
        }

        if ($value === 1) {
            $pwm = 0;

            if ($addOrSub === 0) {
                $pwm = $fadeIn;
            }
        }

        $return .= chr(($inputValue << 7) | ($outputPort & 127));
        $setByte = ($value << 2) | ($blink << 3);

        if ($addOrSub === -1) {
            $setByte += 64;
        } elseif ($addOrSub === 1) {
            $setByte += 128;
        } else {
            $setByte += 192;
        }

        $return .= chr($setByte) . chr($pwm);

        return $return;
    }
}
