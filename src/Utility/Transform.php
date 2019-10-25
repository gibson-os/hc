<?php
namespace GibsonOS\Module\Hc\Utility;

class Transform
{
    /**
     * Macht ASCII zu HEX
     *
     * Macht aus einem ASCII String einen HEX String.
     *
     * @param string $data ASCII String
     * @return string
     */
    public static function asciiToHex($data)
    {
        $return = '';

        for ($i = 0; $i < strlen($data); $i++) {
            $return .= sprintf("%02x", ord($data[$i]));
        }

        return $return;
    }
    /**
     * Macht ASCII zu BIN
     *
     * Macht aus einem ASCII String einen BIN String.
     *
     * @param string $data ASCII String
     * @return string
     */
    public static function asciiToBin($data)
    {
        $return = '';

        for ($i = 0; $i < strlen($data); $i++) {
            $return .= sprintf("%'.08d ", decbin(ord($data[$i])));
        }

        return trim($return);
    }

    /**
     * Macht ASCII zu INT
     *
     * Macht aus einem ASCII String eine INT Zahl.
     *
     * @param string $data ASCII String
     * @param int|null $byte Byte das in INT übersetzt werden soll.
     * @param bool $unsigned Ohne Vorzeichen
     * @return int
     */
    public static function asciiToInt($data, $byte = null, $unsigned = true)
    {
        $return = null;

        if (is_null($byte)) {
            for ($i = 0; $i < strlen($data); $i++) {
                $return = ($return<<8) + ord(substr($data, $i, 1));
            }
        } else {
            $return = ord(substr($data, $byte, 1));
        }

        if (!$unsigned) {
            $return = self::getSignedInt($return);
        }

        return (int)$return;
    }

    /**
     * Macht HEX zu ASCII
     *
     * Macht aus einem HEX String einen ASCII String.
     *
     * @param string $data HEX String
     * @return string
     */
    public static function hexToAscii($data)
    {
        $return = '';

        for ($i = 0; $i < strlen($data); $i += 2) {
            $return .= chr(hexdec(substr($data, $i, 2)));
        }

        return $return;
    }

    /**
     * Macht HEX zu INT
     *
     * Macht aus einem HEX String eine INT Zahl.
     *
     * @param string $data HEX String
     * @param int|null $byte Byte das in INT übersetzt werden soll.
     * @return int
     */
    public static function hexToInt($data, $byte = null)
    {
        if (is_null($byte)) {
            return (int)hexdec($data);
        } else {
            return (int)hexdec(substr($data, $byte*2, 2));
        }
    }

    /**
     * Macht HEX zu BIN
     *
     * Macht aus einem HEX String eine BIN string.
     *
     * @param string $data HEX String
     * @param int|null $byte Byte das in INT übersetzt werden soll.
     * @return string
     */
    public static function hexToBin($data, $byte = null)
    {
        if (is_null($byte)) {
            return sprintf("%'.08d ", decbin(self::hexToInt($data)));
        } else {
            return sprintf("%'.08d ", decbin(substr(self::hexToInt($data, $byte), $byte*2, 2)));
        }
    }

    /**
     * @param string $data
     * @param int|null $byte
     * @return string
     */
    public static function binToAscii($data, $byte = null)
    {
        $data = trim($data);
        $dataBytes = explode(' ', $data);

        foreach ($dataBytes as $key => $dataByte) {
            $dataBytes[$key] = sprintf("%'.08d", $dataByte);
        }

        $data = implode('', $dataBytes);
        $return = '';

        if (is_null($byte)) {
            for ($i = 0; $i < strlen($data); $i += 8) {
                $return .= chr(bindec(substr($data, $i, 8)));
            }
        } else {
            $return = chr(bindec(substr($data, $byte*8, 8)));
        }

        return $return;
    }

    /**
     * @param int $integer
     * @return int
     */
    public static function getSignedInt($integer)
    {
        $bitLength = mb_strlen(decbin($integer));
        $byteLength = ceil($bitLength/8);
        $maxValue = pow(2, $byteLength*8);

        if ($integer >= $maxValue/2) {
            $integer -= $maxValue;
        }

        return (int)$integer;
    }
}