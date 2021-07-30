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

    private const MAX_PAGE = 7;

    private const MAX_COLUMN = 127;

    private const MAX_COLUMN_BYTES = (self::MAX_COLUMN + 1) / 8;

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
    private function sendCommand(Module $slave, int $command, string $data): void
    {
        $this->write($slave, self::COMMAND_COMMAND, chr($command) . $data);
    }
}
