<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper\Io;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Enum\Io\AddOrSub;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Repository\Io\PortRepository;
use GibsonOS\Module\Hc\Service\TransformService;

class DirectConnectMapper
{
    public function __construct(
        private readonly TransformService $transformService,
        private readonly PortRepository $portRepository,
        private readonly ModelWrapper $modelWrapper,
    ) {
    }

    /**
     * @throws SelectError
     */
    public function getDirectConnect(Port $inputPort, string $data): DirectConnect
    {
        $inputValueAndOutputPortByte = $this->transformService->asciiToUnsignedInt($data, 0);
        $setByte = $this->transformService->asciiToUnsignedInt($data, 1);
        $pwmByte = $this->transformService->asciiToUnsignedInt($data, 2);
        $addOrSub = $setByte & 1;

        if (($setByte >> 1) & 1) {
            --$addOrSub;
        }

        $value = (bool) (($setByte >> 2) & 1);

        return (new DirectConnect($this->modelWrapper))
            ->setInputPort($inputPort)
            ->setInputValue((bool) ($inputValueAndOutputPortByte >> 7))
            ->setOutputPort($this->portRepository->getByNumber(
                $inputPort->getModule(),
                $inputValueAndOutputPortByte & 127
            ))
            ->setValue($value)
            ->setPwm($value ? 0 : $pwmByte)
            ->setFadeIn($value ? $pwmByte : 0)
            ->setBlink(($setByte >> 3) & 7)
            ->setAddOrSub(AddOrSub::from($addOrSub))
        ;
    }

    public function getDirectConnectAsString(DirectConnect $directConnect, bool $new): string
    {
        $return = chr($directConnect->getInputPort()->getNumber());

        if (!$new) {
            $return .= chr($directConnect->getOrder());
        }

        $pwm = $directConnect->getPwm();

        if ($directConnect->isValue()) {
            $pwm = 0;

            if ($directConnect->getAddOrSub() === AddOrSub::SET) {
                $pwm = $directConnect->getFadeIn();
            }
        }

        $return .= chr(
            (((int) $directConnect->isInputValue()) << 7) |
            ($directConnect->getOutputPort()->getNumber() & 127)
        );
        $setByte = (((int) $directConnect->isValue()) << 2) | ($directConnect->getBlink() << 3);

        if ($directConnect->getAddOrSub() === AddOrSub::SUB) {
            ++$setByte;
        } elseif ($directConnect->getAddOrSub() === AddOrSub::ADD) {
            $setByte += 2;
        } else {
            $setByte += 3;
        }

        $return .= chr($setByte) . chr($pwm);

        return $return;
    }
}
