<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper\Io;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Dto\Io\Direction;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Io\PortRepository;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use GibsonOS\Module\Hc\Service\TransformService;

class PortMapper
{
    public function __construct(
        private readonly TransformService $transformService,
        private readonly PortRepository $portRepository,
    ) {
    }

    public function getPort(Port $port, string $data): Port
    {
        $byte1 = $this->transformService->asciiToUnsignedInt($data, 0);
        $byte2 = $this->transformService->asciiToUnsignedInt($data, 1);

        $port
            ->setDirection(Direction::from($byte1 & 1))
            ->setValue(($byte1 >> 2 & 1) === 1)
        ;

        if ($port->getDirection() === Direction::INPUT) {
            $port
                ->setDelay(($byte1 >> 3) | $byte2)
                ->setPullUp(($byte1 >> 1 & 1) === 1)
            ;
        } else {
            $port
                ->setPwm($byte2)
                ->setFadeIn(0)
            ;

            if ($port->isValue()) {
                $port
                    ->setFadeIn($port->getPwm())
                    ->setPwm(0)
                ;
            }

            $port->setBlink($byte1 >> 3);
        }

        return $port;
    }

    /**
     * @throws SelectError
     *
     * @return Port[]
     */
    public function getPorts(Module $module, string $data): array
    {
        $byteCount = 0;
        $ports = [];

        foreach ($this->portRepository->getByModule($module) as $port) {
            $ports[] = $this->getPort($port, substr($data, $byteCount, 2));
            $byteCount += 2;
        }

        return $ports;
    }

    public function getPortAsString(Port $port): string
    {
        if ($port->getDirection() === Direction::INPUT) {
            return
                chr(
                    $port->getDirection()->value |
                    (((int) $port->hasPullUp()) << 1) |
                    (((int) $port->isValue()) << 2) |
                    ($port->getDelay() << 3)
                ) .
                chr($port->getDelay());
        }

        $pwm = $port->getPwm();

        if ($port->isValue()) {
            $pwm = $port->getFadeIn();
        }

        return
            chr(
                $port->getDirection()->value |
                (((int) $port->hasPullUp()) << 1) |
                (((int) $port->isValue()) << 2) |
                ($port->getBlink() << 3)
            ) .
            chr($pwm)
        ;
    }

    public function getDirectConnectAsArray(string $data): array
    {
        $inputValueAndOutputPortByte = $this->transformService->asciiToUnsignedInt($data, 0);
        $setByte = $this->transformService->asciiToUnsignedInt($data, 1);
        $pwmByte = $this->transformService->asciiToUnsignedInt($data, 2);
        $addOrSub = $setByte & 1;

        if (($setByte >> 1) & 1) {
            --$addOrSub;
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
            ++$setByte;
        } elseif ($addOrSub === 1) {
            $setByte += 2;
        } else {
            $setByte += 3;
        }

        $return .= chr($setByte) . chr($pwm);

        return $return;
    }
}
