#!/bin/bash
# NepalPay Daily Backup Script
# 
# Setup cron:
#   crontab -e
#   0 2 * * * /path/to/nepal-pay/database/backup_daily.sh >> /var/log/nepalpay_backup.log 2>&1
#
# This runs daily at 2 AM

set -e

# Configuration
PHP="/usr/bin/php"
ROOT="/var/www/nepal-pay"
BACKUP_DIR="$ROOT/backups"
DATE=$(date +%Y-%m-%d)
TIME=$(date +%H%M%S)

echo "=========================================="
echo "NepalPay Database Backup"
echo "Date: $(date)"
echo "=========================================="

# Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

# Run the backup
cd "$ROOT"
$PHP "$ROOT/app/services/BackupService.php" --type=full

if [ $? -eq 0 ]; then
    echo "Backup completed successfully!"
else
    echo "Backup failed! Check logs."
    exit 1
fi

# Clean old backups (keep last 30)
cd "$BACKUP_DIR"
ls -t *.sql.gz | tail -n +31 | xargs -r rm -f

echo "Old backups cleaned up."
echo "=========================================="
echo "Backup Complete: $(date)"
echo "=========================================="

exit 0
