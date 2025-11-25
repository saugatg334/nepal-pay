<?php

namespace chillerlan\QRCode\Data;

use chillerlan\QRCode\Common\Mode;
use chillerlan\QRCode\Common\BitBuffer;

class Byte {
    public static function getData($data, $buffer) {
        $bytes = str_split($data);
        foreach ($bytes as $byte) {
            $buffer->put(ord($byte), 8);
        }
    }

    public static function getLength($data) {
        return strlen($data);
    }
}
