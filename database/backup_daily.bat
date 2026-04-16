@echo off
REM NepalPay Daily Backup Script
REM Run this daily using Windows Task Scheduler
REM 
REM Usage: backup_daily.bat

echo ==========================================
echo NepalPay Database Backup
echo Date: %date% %time%
echo ==========================================

REM Set PHP path
set PHP=C:\xampp\php\php.exe

REM Set NepalPay root
set ROOT=C:\xampp\htdocs\nepal-pay

REM Create backup directory if not exists
if not exist "%ROOT%\backups" mkdir "%ROOT%\backups"

echo Running full database backup...

REM Run the backup using PHP
cd /d %ROOT%
%PHP% "%ROOT%\app\services\BackupService.php" --type=full

if %ERRORLEVEL% EQU 0 (
    echo Backup completed successfully!
) else (
    echo Backup failed! Check logs.
)

echo.
echo ==========================================
echo Backup Complete
echo ==========================================

REM Keep only last 30 backups
powershell -Command "Get-ChildItem -Path '%ROOT%\backups' -Filter '*.sql.gz' | Sort-Object LastWriteTime -Descending | Select-Object -Skip 30 | Remove-Item -Force"

echo Old backups cleaned up.
pause
