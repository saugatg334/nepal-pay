<?php

namespace chillerlan\QRCode;

interface SettingsContainerInterface {
    public function __get($property);
    public function __set($property, $value);
}
