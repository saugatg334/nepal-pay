<?php
echo "Active directory: " . realpath(__DIR__) . "<br>";
echo "Executed file: " . __FILE__ . "<br>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " | " . $_SERVER['REQUEST_URI'] . " | " . realpath(__DIR__) . "\n", FILE_APPEND);
?>
