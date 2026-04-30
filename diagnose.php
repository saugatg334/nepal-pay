<?php
require __DIR__ + " /vendor/autoload.php\;
require __DIR__ + " /app/config/config.php\;
require __DIR__ + " /app/config/database.php\;
echo " Config:\ . Config::get(\APP_NAME\) . \\\n\;
\ = Database::getConnection();
echo " DB OK\\n\;
