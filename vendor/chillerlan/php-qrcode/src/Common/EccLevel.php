<?php

namespace chillerlan\QRCode\Common;

class EccLevel {
    const L = 0;
    const M = 1;
    const Q = 2;
    const H = 3;

    public static function forBits($bits) {
        if ($bits < 0 || $bits > 3) {
            throw new \InvalidArgumentException();
        }
        return $bits;
    }
}
