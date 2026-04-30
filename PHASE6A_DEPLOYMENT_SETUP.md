# Phase 6A: Practical Deployment Setup
## NepalPay Wallet - Production Infrastructure

**Status:** Ready to execute  
**Assumption:** Staging environment available  
**Time Estimate:** 2-3 days

---

## DAY 1: HTTPS + Entry Protection

### 1.1 SSL Certificate (Let's Encrypt)
```bash
# Install certbot
sudo apt update
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d wallet.yourdomain.com

# Auto-renewal (test)
sudo certbot renew --dry-run

# Force HTTPS in Nginx
# Edit: /etc/nginx/sites-available/wallet
```

### 1.2 Nginx Configuration (Copy-Paste Ready)
File: `/etc/nginx/sites-available/wallet`

```nginx
upstream php_handler {
    server unix:/var/run/php/php8.1-fpm.sock;
}

server {
    listen 80;
    server_name wallet.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name wallet.yourdomain.com;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/wallet.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/wallet.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;
    ssl_stapling on;
    ssl_stapling_verify on;

    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; frame-ancestors 'none';" always;

    # Root directory
    root /var/www/wallet/public;
    index index.php;

    # Block sensitive files
    location ~* \.(env|log|sql|backup|bak|yml|yaml|md)$ {
        deny all;
        return 404;
    }

    location ~* \.git {
        deny all;
        return 404;
    }

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api_limit:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=login_limit:10m rate=5r/m;
    limit_conn_zone $binary_remote_addr zone=conn_limit:10m;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass php_handler;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # Security
        fastcgi_param PHP_VALUE "expose_php=off\n";
        fastcgi_hide_header X-Powered-By;
        
        # Timeouts
        fastcgi_read_timeout 30s;
        fastcgi_connect_timeout 30s;
        
        # Buffers
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;

        # Session security
        fastcgi_param PHP_VALUE "session.cookie_httponly=1\nsession.cookie_secure=1\nsession.cookie_samesite=Strict\n";
    }

    # API rate limits
    location /api/ {
        limit_req zone=api_limit burst=20 nodelay;
        limit_conn conn_limit 10;
        try_files $uri $uri/ /api/index.php?$query_string;
    }

    location /api/auth/login {
        limit_req zone=login_limit burst=5 nodelay;
    }

    # Block bad agents
    if ($http_user_agent ~* "sqlmap|nikto|nmap|masscan|zgrab") {
        return 403;
    }

    # Client limits
    client_max_body_size 1m;
    client_body_buffer_size 128k;
}
```

**Apply:**
```bash
sudo ln -s /etc/nginx/sites-available/wallet /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 1.3 Cloudflare (Free Tier)
```
1. Sign up at cloudflare.com
2. Change DNS nameservers
3. Enable:
   - SSL/TLS: Full (strict)
   - WAF: Managed Rules (enable OWASP Core)
   - Rate Limiting: 100 req/5 min per IP
   - Always Use HTTPS: On
```

---

## DAY 2: Database Safety

### 2.1 Backup Script
File: `/opt/scripts/wallet-backup.sh`

```bash
#!/bin/bash
# Wallet Database Backup
# Run daily via cron

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/backups/wallet"
REMOTE_BUCKET="s3://wallet-backups"  # or use scp/NAS

# Retention: Keep 30 days
RETENTION_DAYS=30

mkdir -p $BACKUP_DIR

# Create backup
echo "[$(date)] Starting backup..."
mysqldump -u backup_user -p$MYSQL_PASSWORD \
  --single-transaction \
  --routines \
  --triggers \
  wallet > $BACKUP_DIR/wallet_${DATE}.sql

if [ $? -eq 0 ]; then
    echo "Backup created: wallet_${DATE}.sql"
    
    # Compress
    gzip $BACKUP_DIR/wallet_${DATE}.sql
    
    # Encrypt (optional but recommended)
    # openssl enc -aes-256-cbc -salt -in wallet_${DATE}.sql.gz -out wallet_${DATE}.sql.gz.enc -k $ENCRYPTION_KEY
    
    # Upload to remote storage
    # aws s3 cp $BACKUP_DIR/wallet_${DATE}.sql.gz $REMOTE_BUCKET/
    
    # Clean old backups
    find $BACKUP_DIR -name "*.gz" -mtime +$RETENTION_DAYS -delete
    
    echo "[$(date)] Backup complete"
else
    echo "[$(date)] Backup FAILED"
    # Send alert (see monitoring setup)
fi
```

**Make executable:**
```bash
chmod +x /opt/scripts/wallet-backup.sh
```

### 2.2 Cron Job (Daily Backup)
```bash
# Edit crontab
sudo crontab -e

# Add:
0 2 * * * MYSQL_PASSWORD=your_password /opt/scripts/wallet-backup.sh >> /var/log/wallet-backup.log 2>&1
```

### 2.3 Test Restoration
```bash
# Monthly test (critical!)
cd /opt/backups/wallet
LATEST=$(ls -t wallet_*.sql.gz | head -1)

gunzip < $LATEST | mysql -u root -p wallet_test

# Verify
diff <(mysql -u root -p wallet -e "SELECT COUNT(*) FROM transactions;") \
     <(mysql -u root -p wallet_test -e "SELECT COUNT(*) FROM transactions;")
```

### 2.4 Database User (Restricted)
```sql
-- Don't use root for application!
CREATE USER 'wallet_app'@'localhost' IDENTIFIED BY 'StrongPassword123!';

GRANT SELECT, INSERT, UPDATE, DELETE ON wallet.* TO 'wallet_app'@'localhost';
GRANT SELECT ON wallet.transactions TO 'wallet_app'@'localhost';
-- NO DROP, NO ALTER, NO CREATE

FLUSH PRIVILEGES;

-- Update .env
DB_USER=wallet_app
DB_PASSWORD=StrongPassword123!
```

---

## DAY 3: Secrets Management

### 3.1 Move .env Outside Web Root
```bash
# Current: /var/www/wallet/.env
# Move to: /etc/wallet/.env

sudo mkdir /etc/wallet
sudo mv /var/www/wallet/.env /etc/wallet/
sudo chown www-data:www-data /etc/wallet/.env
sudo chmod 600 /etc/wallet/.env  # Only PHP can read
```

### 3.2 Update PHP to Load External Config
File: `/var/www/wallet/app/config/config.php`

```php
// Load from secure location
$envPath = '/etc/wallet/.env';
if (file_exists($envPath)) {
    self::$dotenv = Dotenv\Dotenv::createImmutable('/etc/wallet');
    self::$dotenv->safeLoad();
}
```

### 3.3 Key Rotation Script
File: `/opt/scripts/rotate-keys.sh`

```bash
#!/bin/bash
# Emergency key rotation

NEW_APP_KEY=$(php -r "echo bin2hex(random_bytes(32));")
NEW_ENCRYPTION_KEY=$(php -r "echo bin2hex(random_bytes(32));")

# 1. Update environment
sed -i "s/APP_KEY=.*/APP_KEY=${NEW_APP_KEY}/" /etc/wallet/.env
sed -i "s/ENCRYPTION_KEY_SALT=.*/ENCRYPTION_KEY_SALT=$(php -r 'echo bin2hex(random_bytes(16));')/" /etc/wallet/.env

# 2. Re-encrypt API tokens (using existing service)
php /var/www/wallet/scripts/rotate-tokens.php

# 3. Reload PHP
sudo systemctl reload php8.1-fpm

# 4. Alert
curl -X POST https://hooks.slack.com/... \
  -d '{"text":"🔐 Keys rotated for NepalPay wallet"}'
```

---

## DAY 4: Basic Monitoring

### 4.1 Error Tracking
File: `/var/www/wallet/monitoring/error-handler.php`

```php
<?php
// Enhanced error handler with alerts

set_exception_handler(function($exception) {
    $message = sprintf(
        "❌ EXCEPTION: %s in %s:%d\nStack: %s",
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    // Log
    error_log($message);
    
    // Alert on critical errors
    if ($exception->getCode() >= 500) {
        sendAlert('CRITICAL', $message);
    }
    
    // User response
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
});

set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() === 0) return;
    
    $levels = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_NOTICE => 'NOTICE'
    ];
    
    $level = $levels[$severity] ?? 'UNKNOWN';
    $msg = "[$level] $message in $file:$line";
    
    error_log($msg);
    
    // Alert on warnings in production
    if ($level === 'WARNING' && APP_ENV === 'production') {
        sendAlert('WARNING', $msg);
    }
});

function sendAlert(string $severity, string $message) {
    // Simple email alert (use your preferred method)
    $to = 'alerts@nepalpay.com';
    $subject = "[$severity] NepalPay Wallet Alert";
    $headers = 'From: monitoring@nepalpay.com';
    
    // Rate limit: don't spam
    $cacheFile = '/tmp/last_alert_' . md5($subject);
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 300)) {
        return; // Wait 5 min between similar alerts
    }
    
    mail($to, $subject, $message, $headers);
    touch($cacheFile);
}
```

Include in `public/index.php`:
```php
require_once __DIR__ . '/../app/monitoring/error-handler.php';
```

### 4.2 Uptime Monitoring (Free Options)
```
1. UptimeRobot (free): Check https://wallet.yourdomain.com every 5 min
2. Health check endpoint: GET /health
   Returns: {"status": "ok", "timestamp": 1234567890}
```

File: `/var/www/wallet/public/health.php`

```php
<?php
try {
    // Check database
    require_once __DIR__ . '/../app/config/database.php';
    $pdo = \NepalPay\Core\Database::getConnection();
    $pdo->query('SELECT 1');
    
    http_response_code(200);
    echo json_encode([
        'status' => 'ok',
        'database' => 'connected',
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
```

### 4.3 Basic Log Aggregation
```bash
# Centralize logs
sudo mkdir /var/log/wallet
sudo touch /var/log/wallet/app.log
sudo chown www-data:www-data /var/log/wallet/app.log

# Update Monolog in your app to write here
// In bootstrap.php
$logger = new \Monolog\Logger('wallet');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('/var/log/wallet/app.log'));
```

---

## DAY 5: Replay Protection Activation

### 5.1 Apply Database Migration
```bash
mysql -u root -p wallet < /var/www/wallet/database/migrations/007_replay_protection.sql

# Verify
mysql -u root -p -e "SHOW TABLES FROM wallet LIKE 'request_nonces';"
```

### 5.2 Enforce Request IDs (Middleware)
File: `/var/www/wallet/app/Middleware/ReplayProtection.php`

```php
<?php
namespace NepalPay\Middleware;

class ReplayProtection {
    public static function handle($next) {
        // Skip CLI
        if (php_sapi_name() === 'cli') {
            return $next();
        }
        
        // Only for API
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== 0) {
            return $next();
        }
        
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? null;
        $timestamp = $_SERVER['HTTP_X_REQUEST_TIMESTAMP'] ?? time();
        
        // Validate
        if (!$requestId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing X-Request-ID header']);
            exit;
        }
        
        if (!class_exists('NepalPay\Services\ReplayProtectionService')) {
            return $next();
        }
        
        if (!\NepalPay\Services\ReplayProtectionService::validateRequest(
            $requestId, 
            (int)$timestamp
        )) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid or replayed request']);
            exit;
        }
        
        return $next();
    }
}
```

Register in API controllers:
```php
// In ApiController constructor
\NepalPay\Middleware\ReplayProtection::handle(function() {
    // Normal execution
});
```

### 5.3 Client Implementation Example
```javascript
// Frontend: All API calls must include
async function apiCall(endpoint, data) {
    const requestId = crypto.randomUUID();
    const timestamp = Math.floor(Date.now() / 1000);
    
    // Sign if you have a secret
    const signature = await signRequest(requestId, timestamp, data);
    
    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'X-Request-ID': requestId,
            'X-Request-Timestamp': timestamp,
            'X-API-Signature': signature,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    });
    
    return response.json();
}
```

---

## WEEK 2: Integration & Testing

### 6.1 SMS/Email Integration (Critical Alerts)

**Option A: Twilio (SMS)**
```bash
composer require twilio/sdk
```

```php
// In AlertService::sendSmsNotification()
use Twilio\Rest\Client;

$twilio = new Client($sid, $token);
$twilio->messages->create(
    '+9779812345678',
    [
        'from' => '+1234567890',
        'body' => "🚨 CRITICAL: $title - $message"
    ]
);
```

**Option B: SendGrid (Email)**
```bash
composer require sendgrid/sendgrid
```

```php
$email = new \SendGrid\Mail\Mail();
$email->setFrom("alerts@nepalpay.com", "NepalPay Security");
$email->setSubject("[CRITICAL] $title");
$email->addTo("security@nepalpay.com");
$email->addContent("text/plain", $message);

$sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
$sendgrid->send($email);
```

### 6.2 Slack Webhook (Team Alerts)
```php
// In AlertService
$webhookUrl = getenv('SLACK_WEBHOOK_URL');
$payload = [
    'text' => "🚨 *$title*\n$message",
    'username' => 'NepalPay Security',
    'icon_emoji' => ':lock:'
];

file_get_contents($webhookUrl, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/json',
        'content' => json_encode($payload)
    ]
]));
```

---

## VERIFICATION CHECKLIST

### Before Go-Live
- [ ] HTTPS redirects work (http → https)
- [ ] Security headers present (curl -I)
- [ ] Database backup runs (check logs)
- [ ] Test restoration works
- [ ] .env outside web root (403 on direct access)
- [ ] API requests rejected without X-Request-ID
- [ ] Rate limits work (curl in loop)
- [ ] Error alerts send (test with trigger)
- [ ] Logs written to centralized location
- [ ] Cloudflare WAF active (check dashboard)

### Quick Test Commands
```bash
# HTTPS check
curl -I http://wallet.yourdomain.com  # Should 301 → https

# Security headers
curl -I https://wallet.yourdomain.com | grep -i "strict-transport-security\|content-security-policy"

# Rate limit (should block after 10)
for i in {1..15}; do curl -w "%{http_code}\n" -o /dev/null -s https://wallet.yourdomain.com/api/test; done

# Sensitive file block
curl -I https://wallet.yourdomain.com/.env  # Should 404

# Health check
curl https://wallet.yourdomain.com/health.php
```

---

## COST ESTIMATE (Monthly)

```
Let's Encrypt SSL:       Free
Cloudflare (free):      Free
UptimeRobot:            Free
Basic SMS (Twilio):     ~$15 (100 msgs)
Basic Email (SendGrid): Free (100/day)
Backups (S3):           ~$2

TOTAL:                  ~$17/month
```

---

## INCIDENT RESPONSE PLAYBOOK

### If Site Goes Down
1. Check: `curl -I https://wallet.yourdomain.com`
2. Check: `sudo systemctl status nginx php8.1-fpm mysql`
3. Check: `/var/log/wallet/app.log` (last 50 lines)
4. Restore from backup if DB corruption
5. Page team via PagerDuty/Slack

### If Fraud Spikes
1. Check: `/var/log/wallet/app.log` for fraud alerts
2. Lower transaction limits temporarily
3. Enable "maintenance mode" (disable transfers)
4. Review fraud assessment logs
5. Whitelist legitimate users, block bad actors

### If Hacked
1. Take site offline (Cloudflare → "Under Attack")
2. Revoke all API tokens: `UPDATE api_tokens SET revoked=1;`
3. Rotate all keys (use rotation script)
4. Restore from clean backup
5. Audit logs for entry point
6. Notify users & authorities

---

## QUESTIONS?

This setup assumes:
- Ubuntu/Debian Linux
- Nginx + PHP-FPM
- MySQL/MariaDB
- Root/sudo access

If different (Apache, CentOS, etc.), adapt accordingly.

Need help with specific step? Just ask.

---

**Version:** 1.0  
**Date:** April 25, 2026  
**Status:** Ready to execute