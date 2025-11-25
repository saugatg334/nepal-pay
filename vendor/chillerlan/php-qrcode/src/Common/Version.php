<?php

namespace chillerlan\QRCode\Common;

class Version {
    public static $VERSION_DECODE_INFO = [
        0x07C94, 0x085BC, 0x09A99, 0x0A4D3, 0x0BBF6, 0x0C762, 0x0D847, 0x0E60D,
        0x0F928, 0x10B78, 0x1145D, 0x12A17, 0x13532, 0x149A6, 0x15683, 0x168C9,
        0x177EC, 0x18EC4, 0x191E1, 0x1AFAB, 0x1B08E, 0x1CC1A, 0x1D33F, 0x1ED75,
        0x1F250, 0x209D5, 0x216F0, 0x228BA, 0x2379F, 0x24B0B, 0x2542E, 0x26A64,
        0x27541, 0x28C69
    ];

    public static $VERSION_BITS = 12;

    public static function getVersionForNumber($versionNumber) {
        if ($versionNumber < 1 || $versionNumber > 40) {
            throw new \InvalidArgumentException();
        }
        return $versionNumber;
    }

    public static function getProvisionalVersionForDimension($dimension) {
        if ($dimension % 4 != 1) {
            throw new \InvalidArgumentException();
        }
        return ($dimension - 17) / 4;
    }

    public static function decodeVersionInformation($versionBits) {
        $bestDifference = PHP_INT_MAX;
        $bestVersion = 0;
        for ($i = 0; $i < count(self::$VERSION_DECODE_INFO); $i++) {
            $targetVersion = self::$VERSION_DECODE_INFO[$i];
            if ($targetVersion == $versionBits) {
                return self::getVersionForNumber($i + 7);
            }
            $bitsDifference = self::numBitsDiffering($versionBits, $targetVersion);
            if ($bitsDifference < $bestDifference) {
                $bestVersion = $i + 7;
                $bestDifference = $bitsDifference;
            }
        }
        if ($bestDifference <= 3) {
            return self::getVersionForNumber($bestVersion);
        }
        return null;
    }

    public static function numBitsDiffering($a, $b) {
        $a ^= $b;
        $count = 0;
        while ($a != 0) {
            $count += $a & 1;
            $a >>= 1;
        }
        return $count;
    }
}
