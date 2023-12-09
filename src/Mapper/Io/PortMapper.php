<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper\Io;

use GibsonOS\Module\Hc\Enum\Io\Direction;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Io\PortRepository;
use GibsonOS\Module\Hc\Service\TransformService;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

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
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
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
}
