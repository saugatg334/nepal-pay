<?php

namespace chillerlan\QRCode\Common;

class Mode {
    const TERMINATOR = 0x00;
    const NUMERIC = 0x01;
    const ALPHANUMERIC = 0x02;
    const STRUCTURED_APPEND = 0x03;
    const BYTE = 0x04;
    const ECI = 0x07;
    const KANJI = 0x08;
    const FNC1_FIRST_POSITION = 0x05;
    const FNC1_SECOND_POSITION = 0x09;
    const HANZI = 0x0D;

    public static function forBits($bits) {
        if ($bits < 0 || $bits > 15) {
            throw new \InvalidArgumentException();
        }
        return $bits;
    }

    public static function getCharacterCountBits($mode, $version) {
        if ($version >= 1 && $version <= 9) {
            $bits = [10, 9, 8, 8];
        } elseif ($version >= 10 && $version <= 26) {
            $bits = [12, 11, 16, 10];
        } elseif ($version >= 27 && $version <= 40) {
            $bits = [14, 13, 16, 12];
        } else {
            throw new \InvalidArgumentException();
        }

        switch ($mode) {
            case self::NUMERIC:
                return $bits[0];
            case self::ALPHANUMERIC:
                return $bits[1];
            case self::BYTE:
                return $bits[2];
            case self::KANJI:
                return $bits[3];
            default:
                throw new \InvalidArgumentException();
        }
    }
}
