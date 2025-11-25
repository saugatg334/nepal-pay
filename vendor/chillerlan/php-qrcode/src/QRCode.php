<?php

namespace chillerlan\QRCode;

use chillerlan\QRCode\Common\BitBuffer;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Mode;
use chillerlan\QRCode\Data\Byte;

class QRCode {
    private $data;
    private $options;

    public function __construct($data, $options = null) {
        $this->data = $data;
        $this->options = $options ?: new QROptions();
    }

    public function render() {
        $buffer = new BitBuffer();
        Byte::getData($this->data, $buffer);
        return $this->createImage($buffer);
    }

    private function createImage($buffer) {
        $size = 21; // Basic QR code size
        $image = imagecreatetruecolor($size, $size);
        $black = imagecolorallocate($image, 0, 0, 0);
        $white = imagecolorallocate($image, 255, 255, 255);

        imagefill($image, 0, 0, $white);

        // Simple pattern for demo
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (($x + $y) % 2 == 0) {
                    imagesetpixel($image, $x, $y, $black);
                }
            }
        }

        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($imageData);
    }
}
