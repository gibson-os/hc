<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Service\AbstractService;

class TransformService extends AbstractService
{
    /**
     * Macht ASCII zu HEX.
     *
     * Macht aus einem ASCII String einen HEX String.
     *
     * @param string $data ASCII String
     */
    public function asciiToHex(string $data): string
    {
        $return = '';

        for ($i = 0; $i < strlen($data); ++$i) {
            $return .= sprintf('%02x', ord($data[$i]));
        }

        return $return;
    }

    /**
     * Macht ASCII zu BIN.
     *
     * Macht aus einem ASCII String einen BIN String.
     *
     * @param string $data ASCII String
     */
    public function asciiToBin(string $data): string
    {
        $return = '';

        for ($i = 0; $i < strlen($data); ++$i) {
            $return .= sprintf("%'.08d ", decbin(ord($data[$i])));
        }

        return trim($return);
    }

    /**
     * Macht ASCII zu INT.
     *
     * Macht aus einem ASCII String eine INT Zahl.
     *
     * @param string   $data     ASCII String
     * @param int|null $byte     byte das in INT übersetzt werden soll
     * @param bool     $unsigned Ohne Vorzeichen
     */
    public function asciiToInt(string $data, int $byte = null, bool $unsigned = true): int
    {
        $return = 0;

        if ($byte === null) {
            for ($i = 0; $i < strlen($data); ++$i) {
                $return = ($return << 8) + ord(substr($data, $i, 1));
            }
        } else {
            $return = ord(substr($data, $byte, 1));
        }

        if (!$unsigned) {
            $return = self::getSignedInt($return);
        }

        return $return;
    }

    /**
     * Macht HEX zu ASCII.
     *
     * Macht aus einem HEX String einen ASCII String.
     *
     * @param string $data HEX String
     */
    public function hexToAscii(string $data): string
    {
        $return = '';

        for ($i = 0; $i < strlen($data); $i += 2) {
            $return .= chr((int) hexdec(substr($data, $i, 2)));
        }

        return $return;
    }

    /**
     * Macht HEX zu INT.
     *
     * Macht aus einem HEX String eine INT Zahl.
     *
     * @param string   $data HEX String
     * @param int|null $byte byte das in INT übersetzt werden soll
     */
    public function hexToInt(string $data, int $byte = null): int
    {
        if ($byte === null) {
            return (int) hexdec($data);
        }

        return (int) hexdec(substr($data, $byte * 2, 2));
    }

    /**
     * Macht HEX zu BIN.
     *
     * Macht aus einem HEX String eine BIN string.
     *
     * @param string   $data HEX String
     * @param int|null $byte byte das in INT übersetzt werden soll
     */
    public function hexToBin(string $data, int $byte = null): string
    {
        if ($byte === null) {
            return sprintf("%'.08d ", decbin(self::hexToInt($data)));
        }

        return sprintf(
            "%'.08d ",
            decbin((int) substr((string) self::hexToInt($data, $byte), $byte * 2, 2))
        );
    }

    public function binToAscii(string $data, int $byte = null): string
    {
        $data = trim($data);
        $dataBytes = explode(' ', $data);

        foreach ($dataBytes as $key => $dataByte) {
            $dataBytes[$key] = sprintf("%'.08d", $dataByte);
        }

        $data = implode('', $dataBytes);
        $return = '';

        if ($byte === null) {
            for ($i = 0; $i < strlen($data); $i += 8) {
                $return .= chr(bindec(substr($data, $i, 8)));
            }
        } else {
            $return = chr(bindec(substr($data, $byte * 8, 8)));
        }

        return $return;
    }

    public function getSignedInt(int $integer): int
    {
        $bitLength = mb_strlen(decbin($integer));
        $byteLength = ceil($bitLength / 8);
        $maxValue = pow(2, $byteLength * 8);

        if ($integer >= $maxValue / 2) {
            $integer -= $maxValue;
        }

        return (int) $integer;
    }
}
