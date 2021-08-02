<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;

class Ssd1306Service extends AbstractSlave
{
    private const COMMAND_COMMAND = 0;

    private const COMMAND_DATA = 64;

    private const COMMAND_PAGE_START = 11;

    private const COMMAND_DISPLAY_ON = 175;

    private const COMMAND_DISPLAY_OFF = 174;

    private const COMMAND_SET_MEMORY_ADDRESSING_MODE = 20;

    private const COMMAND_SET_LOWER_COLUMN_START = 0;

    private const COMMAND_SET_HIGHER_COLUMN_START = 16;

    private const COMMAND_SET_START_LINE_START = 40;

    private const COMMAND_SET_CONTRAST_CONTROL = 129;

    private const MAX_PAGE = 7;

    private const MAX_COLUMN = 127;

    private const MAX_COLUMN_BYTES = (self::MAX_COLUMN + 1) / 8;

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

    public function handshake(Module $slave): Module
    {
        return $slave;
    }

    /**
     * @param int[][] $data
     *
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function sendData(Module $slave, array $data): Ssd1306Service
    {
        foreach ($data as $row) {
            if (count($row) > self::MAX_COLUMN_BYTES) {
                throw new WriteException(sprintf(
                    'Row data to large. %d Bytes allowed. %d Bytes tried to send',
                    self::MAX_COLUMN_BYTES,
                    count($row)
                ));
            }

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

        $this->sendCommand($slave, self::COMMAND_SET_MEMORY_ADDRESSING_MODE, chr($addressingMode));

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
        $this->sendCommand($slave, self::COMMAND_SET_LOWER_COLUMN_START + $lowColumnAddress);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setHighColumnAddress(Module $slave, int $highColumnAddress = 0): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_SET_HIGHER_COLUMN_START + $highColumnAddress);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setStartLineAddress(Module $slave, int $startLineAddress = 0): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_SET_START_LINE_START + $startLineAddress);

        return $this;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function setContrastControl(Module $slave, int $contrast = 0): Ssd1306Service
    {
        $this->sendCommand($slave, self::COMMAND_SET_CONTRAST_CONTROL, chr($contrast));

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
