
<?php
error_log("TRACE: " . __FILE__ . " | REQUEST_URI: " . $_SERVER['REQUEST_URI'] . " | SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);
file_put_contents(__DIR__ . '/trace.log', date('Y-m-d H:i:s') . ' | ' . $_SERVER['REQUEST_URI'] . ' | ' . realpath(__DIR__) . "\n", FILE_APPEND);
echo "Trace logged to trace.log. Check error.log too.";
?>

