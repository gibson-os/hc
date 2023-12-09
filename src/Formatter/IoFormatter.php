<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use Exception;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\TwigService;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Enum\Direction;
use GibsonOS\Module\Hc\Enum\Io\AddOrSub;
use GibsonOS\Module\Hc\Enum\Io\Direction as IoDirection;
use GibsonOS\Module\Hc\Mapper\Io\DirectConnectMapper;
use GibsonOS\Module\Hc\Mapper\Io\PortMapper;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Repository\Io\PortRepository;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Module\AbstractHcModule;
use GibsonOS\Module\Hc\Service\Module\IoService;
use GibsonOS\Module\Hc\Service\TransformService;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

class IoFormatter extends AbstractHcFormatter
{
    private ?int $directConnectReadInputPort = null;

    public function __construct(
        TransformService $transformService,
        TwigService $twigService,
        TypeRepository $typeRepository,
        ModelWrapper $modelWrapper,
        private readonly PortRepository $portRepository,
        private readonly LogRepository $logRepository,
        private readonly PortMapper $portMapper,
        private readonly DirectConnectMapper $directConnectMapper,
    ) {
        parent::__construct($transformService, $twigService, $typeRepository, $modelWrapper);
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function command(Log $log): ?string
    {
        $command = $log->getCommand();

        switch ($command) {
            case IoService::COMMAND_ADD_DIRECT_CONNECT:
                return 'DC hinzufügen';
            case IoService::COMMAND_SET_DIRECT_CONNECT:
                return 'DC überschreiben';
            case IoService::COMMAND_DELETE_DIRECT_CONNECT:
                return 'DC löschen';
            case IoService::COMMAND_RESET_DIRECT_CONNECT:
                return 'DC reseten';
            case IoService::COMMAND_READ_DIRECT_CONNECT:
                return 'DC lesen';
            case IoService::COMMAND_DEFRAGMENT_DIRECT_CONNECT:
                return 'DC defragmentieren';
            case IoService::COMMAND_STATUS_IN_EEPROM:
                return 'Status in EEPROM';
            case IoService::COMMAND_DIRECT_CONNECT_STATUS:
                return 'DC aktiviert';
        }

        $module = $log->getModule();

        if ($module !== null && $command !== null && $command < (int) $module->getConfig()) {
            return $this->portRepository->getByNumber($module, $command)->getName();
        }

        return parent::command($log);
    }

    /**
     * @throws Throwable
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function text(Log $log): ?string
    {
        switch ($log->getCommand()) {
            case AbstractHcModule::COMMAND_CONFIGURATION:
                return 'Port Anzahl: ' . $this->transformService->asciiToUnsignedInt($log->getRawData());
            case IoService::COMMAND_DEFRAGMENT_DIRECT_CONNECT:
                return 'Defragmentieren';
            case IoService::COMMAND_READ_DIRECT_CONNECT:
                if ($log->getDirection() === Direction::OUTPUT) {
                    return null;
                }

                $lastByte = $this->transformService->asciiToUnsignedInt($log->getRawData(), 2);

                if ($lastByte === IoService::DIRECT_CONNECT_READ_NOT_SET) {
                    return 'Kein Port gesetzt';
                }

                if ($lastByte === IoService::DIRECT_CONNECT_READ_NOT_EXIST) {
                    return 'Kein Befehl vorhanden';
                }

                return null;
            case IoService::COMMAND_DIRECT_CONNECT_STATUS:
                return $this->transformService->asciiToUnsignedInt($log->getRawData(), 0) ? 'Aktiv' : 'Inaktiv';
            case IoService::COMMAND_STATUS_IN_EEPROM:
                if ($log->getDirection() == Direction::OUTPUT) {
                    return 'Standard gesetzt';
                }

                if ($this->transformService->asciiToUnsignedInt($log->getRawData(), 0)) {
                    return 'Standard geladen';
                }

                return 'Standard nicht vorhanden';
        }

        return parent::text($log);
    }

    /**
     * @throws Exception
     */
    public function render(Log $log): ?string
    {
        $module = $log->getModule();

        if ($module === null) {
            return null;
        }

        switch ($log->getCommand()) {
            case AbstractHcModule::COMMAND_STATUS:
            case AbstractHcModule::COMMAND_DATA_CHANGED:
                $changedPorts = $this->getChangedPorts($log);

                if (!count($changedPorts)) {
                    return 'Keine Änderungen';
                }

                $return =
                    '<table>' .
                        '<tr>' .
                            '<th>&nbsp;</th>' .
                            '<th>Richtung</th>' .
                            '<th>Zustand</th>' .
                            '<th>PullUp</th>' .
                            '<th>Verzögerung</th>' .
                            '<th>PWM</th>' .
                            '<th>Blinken</th>' .
                        '</tr>';

                foreach ($changedPorts as $port) {
                    $return .=
                        '<tr>' .
                            '<td>' . $port->getName() . '</td>' .
                            '<td>' . ($port->getDirection() === IoDirection::INPUT ? 'Eingang' : 'Ausgang') . '</td>' .
                            '<td>' . $port->getValueNames()[(int) $port->isValue()] . '</td>' .
                            ($port->getDirection() === IoDirection::INPUT
                                ? '<td>' . ($port->hasPullUp() ? 'Ja' : 'Nein') . '</td>' .
                                  '<td>' . $port->getDelay() . '</td>'
                                : '') .
                            ($port->getDirection() === IoDirection::OUTPUT
                                ? '<td>' . $port->getPwm() . '</td>' .
                                  '<td>' . $port->getBlink() . '</td>'
                                : '') .
                        '</tr>';
                }

                return $return . '</table>';
            case IoService::COMMAND_ADD_DIRECT_CONNECT:
                $inputPort = $this->transformService->asciiToUnsignedInt($log->getRawData(), 0);
                $input = $this->portRepository->getByNumber($module, $inputPort);

                return
                    '<table>' .
                        '<tr>' .
                            '<th>Eingangs Port</th>' .
                            '<td>' . $input->getName() . '</td>' .
                        '</tr>' .
                        $this->getDirectConnectTableRows($log, $input, substr($log->getRawData(), 1)) .
                    '</table>';
            case IoService::COMMAND_SET_DIRECT_CONNECT:
                $inputPort = $this->transformService->asciiToUnsignedInt($log->getRawData(), 0);
                $input = $this->portRepository->getByNumber($module, $inputPort);

                return
                    '<table>' .
                        '<tr>' .
                            '<th>Eingangs Port</th>' .
                            '<td>' . $input->getName() . '</td>' .
                        '</tr>' .
                        '<tr>' .
                            '<th>Nummer</th>' .
                            '<td>' . $this->transformService->asciiToUnsignedInt($log->getRawData(), 1) . '</td>' .
                        '</tr>' .
                    $this->getDirectConnectTableRows($log, $input, substr($log->getRawData(), 2)) .
                    '</table>';
            case IoService::COMMAND_DELETE_DIRECT_CONNECT:
                $inputPort = $this->transformService->asciiToUnsignedInt($log->getRawData(), 0);
                $input = $this->portRepository->getByNumber($module, $inputPort);

                return
                    '<table>' .
                        '<tr>' .
                            '<th>Eingangs Port</th>' .
                            '<td>' . $input->getName() . '</td>' .
                        '</tr>' .
                        '<tr>' .
                            '<th>Nummer</th>' .
                            '<td>' . $this->transformService->asciiToUnsignedInt($log->getRawData(), 1) . '</td>' .
                        '</tr>' .
                    '</table>';
            case IoService::COMMAND_RESET_DIRECT_CONNECT:
                $inputPort = $this->transformService->asciiToUnsignedInt($log->getRawData(), 0);
                $input = $this->portRepository->getByNumber($module, $inputPort);

                return
                    '<table>' .
                        '<tr>' .
                            '<th>Eingangs Port</th>' .
                            '<td>' . $input->getName() . '</td>' .
                        '</tr>' .
                    '</table>';
            case IoService::COMMAND_READ_DIRECT_CONNECT:
                $inputPort = $this->directConnectReadInputPort;

                if ($log->getDirection() === Direction::OUTPUT) {
                    $inputPort = $this->transformService->asciiToUnsignedInt($log->getRawData(), 0);
                    $input = $this->portRepository->getByNumber($module, $inputPort);

                    return
                        '<table>' .
                            '<tr>' .
                                '<th>Eingangs Port</th>' .
                                '<td>' . $input->getName() . '</td>' .
                            '</tr>' .
                            '<tr>' .
                                '<th>Nummer</th>' .
                                '<td>' . $this->transformService->asciiToUnsignedInt($log->getRawData(), 1) . '</td>' .
                            '</tr>' .
                        '</table>';
                }

                $lastByte = $this->transformService->asciiToUnsignedInt($log->getRawData(), 2);

                if ($lastByte == IoService::DIRECT_CONNECT_READ_NOT_SET) {
                    return null;
                }

                if ($lastByte == IoService::DIRECT_CONNECT_READ_NOT_EXIST) {
                    return null;
                }

                if ($inputPort === null) {
                    return null;
                }

                return
                    '<table>' .
                        $this->getDirectConnectTableRows(
                            $log,
                            $this->portRepository->getByNumber($module, $inputPort),
                            $log->getRawData()
                        ) .
                    '</table>';
            case IoService::COMMAND_STATUS_IN_EEPROM:
                // return 'Status in EEPROM';
        }

        if ($log->getType() === MasterService::TYPE_DATA && $log->getCommand() < (int) $module->getConfig()) {
            $port = $this->portMapper->getPort(new Port($this->modelWrapper), $log->getRawData());

            $return =
                '<table>' .
                '<tr><th>Richtung</th><td>' . ($port->getDirection() === IoDirection::INPUT ? 'Eingang' : 'Ausgang') . '</td></tr>' .
                '<tr><th>Zustand</th><td>' . $port->getValueNames()[(int) $port->isValue()] . '</td></tr>';

            if ($port->getDirection() === IoDirection::INPUT) {
                $return .=
                    '<tr><th>PullUp</th><td>' . ($port->hasPullUp() ? 'Ja' : 'Nein') . '</td></tr>' .
                    '<tr><th>Verzögerung</th><td>' . $port->getDelay() . '</td></tr>';
            } else {
                $return .=
                    '<tr><th>PWM</th><td>' . $port->getPwm() . '</td></tr>' .
                    '<tr><th>Blinken</th><td>' . $port->getBlink() . '</td></tr>';
            }

            return $return . '</table>';
        }

        return parent::render($log);
    }

    /**
     * @throws SelectError
     *
     * @return Port[]
     */
    private function getChangedPorts(Log $log): array
    {
        $module = $log->getModule();

        if ($module === null) {
            return [];
        }

        $ports = $this->portMapper->getPorts($module, $log->getRawData());

        if ($log->getId() === 0) {
            return $ports;
        }

        try {
            $lastData = $this->logRepository->getPreviousEntryByModuleId(
                (int) $log->getId(),
                (int) $module->getId(),
                $log->getCommand(),
                $log->getType(),
                $log->getDirection()
            )->getRawData();
        } catch (SelectError) {
            return $ports;
        }

        $lastPorts = $this->portMapper->getPorts($module, $lastData);
        $changedPorts = [];

        foreach ($ports as $number => $port) {
            $lastPort = $lastPorts[$number];

            if ($lastPort->getDirection() !== $port->getDirection()) {
                $changedPorts[$number] = $port;

                continue;
            }

            if ($lastPort->isValue() !== $port->isValue()) {
                $changedPorts[$number] = $port;

                continue;
            }

            if ($port->getDirection() === IoDirection::INPUT) {
                if (
                    $lastPort->getDelay() !== $port->getDelay()
                    || $lastPort->hasPullUp() !== $port->hasPullUp()
                ) {
                    $changedPorts[$number] = $port;
                }
            } elseif (
                $lastPort->getPwm() !== $port->getPwm()
                || $lastPort->getFadeIn() !== $port->getFadeIn()
                || $lastPort->getBlink() !== $port->getBlink()
            ) {
                $changedPorts[$number] = $port;
            }
        }

        return $changedPorts;
    }

    /**
     * @throws Exception
     */
    private function getDirectConnectTableRows(Log $log, Port $inputPort, string $data): string
    {
        $module = $log->getModule();

        if ($module === null) {
            return '<tr></tr>';
        }

        $directConnect = $this->directConnectMapper->getDirectConnect($inputPort, $data);
        $outputPort = $directConnect->getOutputPort();

        $addOrSub = 'Setzen';

        if ($directConnect->getAddOrSub() === AddOrSub::ADD) {
            $addOrSub = 'Addieren';
        } elseif ($directConnect->getAddOrSub() === AddOrSub::SUB) {
            $addOrSub = 'Subtrahieren';
        }

        return
            '<tr>' .
                '<th>Eingangs Zustand</th>' .
                '<td>' . $inputPort->getValueNames()[(int) $directConnect->isInputValue()] . '</td>' .
            '</tr>' .
            '<tr>' .
                '<th>Ausgangs Port</th>' .
                '<td>' . $outputPort->getName() . '</td>' .
            '</tr>' .
            '<tr>' .
                '<th>PWM</th>' .
                '<td>' . $directConnect->getPwm() . '</td>' .
            '</tr>' .
            '<tr>' .
                '<th>Blinken</th>' .
                '<td>' . $directConnect->getBlink() . '</td>' .
            '</tr>' .
            '<tr>' .
                '<th>Einblenden</th>' .
                '<td>' . $directConnect->getFadeIn() . '</td>' .
            '</tr>' .
            '<tr>' .
                '<th>Wert</th>' .
                '<td>' . $outputPort->getValueNames()[(int) $directConnect->isValue()] . '</td>' .
            '</tr>' .
            '<tr>' .
                '<th>Anwenden</th>' .
                '<td>' . $addOrSub . '</td>' .
            '</tr>';
    }
}
