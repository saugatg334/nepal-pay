@echo off
cd /d "d:\xampp\htdocs\nepal-pay"
d:\xampp\mysql\bin\mysqldump.exe -u root nepalpay > "backups/db_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%.sql"
echo Backup completed: %date% %time%

