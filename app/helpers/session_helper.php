<?php
if (!session_id()) {
    session_start();
}

function setFlash($key, $message) {
    $_SESSION[$key] = $message;
}

function getFlash($key) {
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return '';
}
?>
