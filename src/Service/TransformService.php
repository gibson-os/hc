<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

class TransformService
{
    private const HERTZ_UNITS = ['Hz', 'kHz', 'MHz', 'GHz'];

    public function asciiToHex(string $asciiString): string
    {
        $return = '';

        for ($i = 0; $i < strlen($asciiString); ++$i) {
            $return .= sprintf('%02x', ord($asciiString[$i]));
        }

        return $return;
    }

    public function asciiToBin(string $asciiString, int $byte = null): string
    {
        if ($byte !== null) {
            return sprintf("%'.08d", decbin(ord(substr($asciiString, $byte, 1))));
        }

        $return = '';

        for ($i = 0; $i < strlen($asciiString); ++$i) {
            $return .= sprintf("%'.08d ", decbin(ord(substr($asciiString, $i, 1))));
        }

        return trim($return);
    }

    public function asciiToUnsignedInt(string $asciiString, int $byte = null): int
    {
        $return = 0;

        if ($byte === null) {
            for ($i = 0; $i < strlen($asciiString); ++$i) {
                $return = ($return << 8) + ord(substr($asciiString, $i, 1));
            }
        } else {
            $return = ord(substr($asciiString, $byte, 1));
        }

        return $return;
    }

    public function asciiToSignedInt(string $asciiString, int $byte = null): int
    {
        return $this->getSignedInt($this->asciiToUnsignedInt($asciiString, $byte));
    }

    public function hexToAscii(string $hexString): string
    {
        $return = '';

        for ($i = 0; $i < strlen($hexString); $i += 2) {
            $return .= chr(hexdec(substr($hexString, $i, 2)));
        }

        return $return;
    }

    public function hexToInt(string $hexString, int $byte = null): int
    {
        if ($byte === null) {
            return hexdec($hexString);
        }

        return hexdec(substr($hexString, $byte * 2, 2));
    }

    public function hexToBin(string $hexString, int $byte = null): string
    {
        if ($byte !== null) {
            return sprintf("%'.08d", decbin($this->hexToInt($hexString, $byte)));
        }

        $return = '';

        for ($i = 0; $i < strlen($hexString) / 2; ++$i) {
            $return .= sprintf("%'.08d ", decbin($this->hexToInt($hexString, $i)));
        }

        return trim($return);
    }

    public function binToAscii(string $binString, int $byte = null): string
    {
        $binString = trim($binString);
        $dataBytes = explode(' ', $binString);

        foreach ($dataBytes as $key => $dataByte) {
            $dataBytes[$key] = sprintf("%'.08d", $dataByte);
        }

        $return = '';

        if ($byte === null) {
            for ($i = 0; $i < count($dataBytes); ++$i) {
                $return .= chr((int) bindec($dataBytes[$i]));
            }
        } else {
            $return = chr((int) bindec($dataBytes[$byte]));
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

    public function transformHertz(int $hertz): string
    {
        for ($i = 0; $hertz > 1000; $hertz /= 1000) {
            ++$i;
        }

        return str_replace('.', ',', (string) $hertz) . ' ' . self::HERTZ_UNITS[$i];
    }
}
