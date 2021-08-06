<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Module\Hc\Dto\Ssd1306\Pixel;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;

class Ssd1306Service extends AbstractSlave
{
    private const COMMAND_COMMAND = 0;

    private const COMMAND_DATA = 64;

    private const COMMAND_PAGE_START = 11;

    private const COMMAND_DISPLAY_ON = 175;

    private const COMMAND_DISPLAY_OFF = 174;

    private const COMMAND_ENTIRE_DISPLAY_ON = 165;

    private const COMMAND_ENTIRE_DISPLAY_OFF = 164;

    private const COMMAND_MEMORY_ADDRESSING_MODE = 20;

    private const COMMAND_LOWER_COLUMN_START = 0;

    private const COMMAND_HIGHER_COLUMN_START = 16;

    private const COMMAND_START_LINE_START = 40;

    private const COMMAND_CONTRAST_CONTROL = 129;

    private const COMMAND_MULTIPLEX_RATIO = 168;

    private const COMMAND_DISPLAY_OFFSET = 211;

    private const COMMAND_DISPLAY_CLOCK_DIVIDE = 213;

    private const COMMAND_PRE_CHARGE_PERIOD = 217;

    private const COMMAND_COM_PINS_HARDWARE_CONFIGURATION = 218;

    private const COMMAND_VCOMH_DESELECT_LEVEL = 219;

    private const COMMAND_CHARGE_PUMP_SETTING = 141;

    public const MAX_PAGE = 7;

    public const MAX_COLUMN = 127;

    public const MAX_BIT = 7;

    public const COM_OUTPUT_SCAN_DIRECTION_NORMAL = 192;

    public const COM_OUTPUT_SCAN_DIRECTION_REMAPPED = 200;

    private const COM_OUTPUT_SCAN_DIRECTIONS = [
        self::COM_OUTPUT_SCAN_DIRECTION_NORMAL,
        self::COM_OUTPUT_SCAN_DIRECTION_REMAPPED,
    ];

    public const ADDRESSING_MODE_HORIZONTAL = 0;

    public const ADDRESSING_MODE_VERTICAL = 1;

    public const ADDRESSING_MODE_PAGE = 16;

    private const ADDRESSING_MODES = [
        self::ADDRESSING_MODE_HORIZONTAL,
        self::ADDRESSING_MODE_VERTICAL,
        self::ADDRESSING_MODE_PAGE,
    ];

    public const DISPLAY_MODE_NORMAL = 166;

    public const DISPLAY_MODE_INVERSE = 167;

    private const DISPLAY_MODES = [
        self::DISPLAY_MODE_NORMAL,
        self::DISPLAY_MODE_INVERSE,
    ];

    public const DESELECT_LEVEL_0_65 = 0;

    public const DESELECT_LEVEL_0_77 = 32;

    public const DESELECT_LEVEL_0_83 = 48;

    private const DESELECT_LEVELS = [
        self::DESELECT_LEVEL_0_65,
        self::DESELECT_LEVEL_0_77,
        self::DESELECT_LEVEL_0_83
    ];

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function handshake(Module $slave): Module
    {
        $this
            ->setDisplayOff($slave)
            ->setMemoryAddressingMode($slave, self::ADDRESSING_MODE_HORIZONTAL)
            ->setPageStart($slave)
            ->setComOutputScanDirection($slave, self::COM_OUTPUT_SCAN_DIRECTION_REMAPPED)
            ->setLowColumnAddress($slave)
            ->setHighColumnAddress($slave)
            ->setStartLineAddress($slave)
            ->setContrastControl($slave, 63)
            ->setSegmentReMap($slave, true)
            ->setDisplayMode($slave, self::DISPLAY_MODE_NORMAL)
            ->setMultiplexRatio($slave)
            ->setEntireDisplayOff($slave)
            ->setDisplayOffset($slave)
            ->setDisplayClockDivide($slave, 240)
            ->setPreChargePeriod($slave, 22)
            ->setComPinsHardwareConfiguration($slave, true, false)
            ->setVcomhDeselectLevel($slave, self::DESELECT_LEVEL_0_77)
            ->setChargePumpSetting($slave, true)
        ;

        return $slave;
    }

    /**
     * @param int[][] $data
     *
     * @throws AbstractException
     * @throws SaveError
     */
    public function sendData(Module $slave, array $data): Ssd1306Service
    {
        foreach ($data as $row) {
            $this->write(
                $slave,
                self::COMMAND_DATA,
                implode('', array_map(fn (int $columnByte) => chr($columnByte), $row))
            );
        }

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function setPageStart(Module $slave, int $page = 0, int $column = 0): Ssd1306Service
    {
        if ($page > self::MAX_PAGE) {
            throw new WriteException(sprintf(
                'Page %d is to big. Max %d allowed.',
                $page,
                self::MAX_PAGE
            ));
        }

        if ($column > self::MAX_COLUMN) {
            throw new WriteException(sprintf(
                'Column %d is to big. Max %d allowed.',
                $column,
                self::MAX_COLUMN
            ));
        }

        $this->sendCommand(
            $slave,
            self::COMMAND_PAGE_START + $page,
            chr(33) . chr($column) . chr(127)
        );

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setDisplayOn(Module $slave): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_DISPLAY_ON);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setDisplayOff(Module $slave): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_DISPLAY_OFF);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setEntireDisplayOn(Module $slave): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_ENTIRE_DISPLAY_ON);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setEntireDisplayOff(Module $slave): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_ENTIRE_DISPLAY_OFF);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function setMemoryAddressingMode(Module $slave, int $addressingMode): Ssd1306Service
    {
        if (!in_array($addressingMode, self::ADDRESSING_MODES)) {
            throw new WriteException(sprintf(
                'Addressing Mode %d not allowed. Possible: %s',
                $addressingMode,
                implode(', ', self::ADDRESSING_MODES)
            ));
        }

        $this->sendCommand($slave, self::COMMAND_MEMORY_ADDRESSING_MODE, chr($addressingMode));

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function setComOutputScanDirection(Module $slave, int $scanDirection): Ssd1306Service
    {
        if (!in_array($scanDirection, self::COM_OUTPUT_SCAN_DIRECTIONS)) {
            throw new WriteException(sprintf(
                'Scan direction %d not allowed. Possible: %s',
                $scanDirection,
                implode(', ', self::COM_OUTPUT_SCAN_DIRECTIONS)
            ));
        }

        $this->sendCommand($slave, $scanDirection);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setLowColumnAddress(Module $slave, int $lowColumnAddress = 0): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_LOWER_COLUMN_START + $lowColumnAddress);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setHighColumnAddress(Module $slave, int $highColumnAddress = 0): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_HIGHER_COLUMN_START + $highColumnAddress);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setStartLineAddress(Module $slave, int $startLineAddress = 0): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_START_LINE_START + $startLineAddress);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setContrastControl(Module $slave, int $contrast = 0): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_CONTRAST_CONTROL, chr($contrast));

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function setDisplayMode(Module $slave, int $displayMode): Ssd1306Service
    {
        if (!in_array($displayMode, self::DISPLAY_MODES)) {
            throw new WriteException(sprintf(
                'Display mode %d not allowed. Possible: %s',
                $displayMode,
                implode(', ', self::DISPLAY_MODES)
            ));
        }

        $this->sendCommand($slave, $displayMode);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setDisplayOffset(Module $slave, int $offset = 0): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_DISPLAY_OFFSET, chr($offset));

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setDisplayClockDivide(Module $slave, int $clockDivide = 0): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_DISPLAY_CLOCK_DIVIDE, chr($clockDivide));

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setPreChargePeriod(Module $slave, int $preChargePeriod = 0): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_PRE_CHARGE_PERIOD, chr($preChargePeriod));

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setSegmentReMap(Module $slave, bool $mappedToAddress127): Ssd1306Service
    {
        $this->sendCommand($slave, 160 + (int) $mappedToAddress127);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setComPinsHardwareConfiguration(
        Module $slave,
        bool $alternativeConfiguration,
        bool $leftRightRemap
    ): Ssd1306Service {
        $data = 2;
        $data |= ((int) $alternativeConfiguration) << 4;
        $data |= ((int) $leftRightRemap) << 5;

        $this->sendCommand($slave, self::COMMAND_COM_PINS_HARDWARE_CONFIGURATION, chr($data));

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function setVcomhDeselectLevel(Module $slave, int $deselectLevel = 0): Ssd1306Service
    {
        if (!in_array($deselectLevel, self::DESELECT_LEVELS)) {
            throw new WriteException(sprintf(
                'Vcomh deselect level %d not allowed. Possible: %s',
                $deselectLevel,
                implode(', ', self::DESELECT_LEVELS)
            ));
        }

        $this->sendCommand($slave, self::COMMAND_VCOMH_DESELECT_LEVEL, chr($deselectLevel));

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setChargePumpSetting(Module $slave, bool $enableChargePump): Ssd1306Service
    {
        $this->sendCommand(
            $slave,
            self::COMMAND_CHARGE_PUMP_SETTING,
            chr((16 + (int) $enableChargePump) << 2)
        );

        if ($enableChargePump) {
            $this->setDisplayOn($slave);
        }

        return $this;
    }

    /**
     * @param array<int, array<int, array<int, Pixel>>> $pixels
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function writePixels(Module $slave, array $pixels): Ssd1306Service
    {
        ksort($pixels);
        $data = '';

        $this->setPageStart($slave, 0, 0);

        foreach ($pixels as $page) {
            ksort($page);

            foreach ($page as $column) {
                ksort($column);
                $columnData = 0;

                foreach ($column as $pixel) {
                    $columnData |= ((int) $pixel->isOn()) << $pixel->getBit();
                }

                $data .= chr($columnData);

                if (strlen($data) === 32) {
                    $this->write($slave, self::COMMAND_DATA, $data);
                    $data = '';
                }
            }

            if (strlen($data) > 0) {
                $this->write($slave, self::COMMAND_DATA, $data);
            }
        }

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    private function setMultiplexRatio(Module $slave): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_MULTIPLEX_RATIO, chr(63));

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    private function sendCommand(Module $slave, int $command, string $data = ''): void
    {
        $this->write($slave, self::COMMAND_COMMAND, chr($command) . $data);
    }
}
