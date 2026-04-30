# Production Deployment Guide
## NepalPay Wallet - Phase 6 Hardening

---

## 🚨 CRITICAL MISSING PIECES (Pre-Deployment)

The following MUST be completed before ANY production deployment:

### 1. ❌ Infrastructure Security (URGENT)
**Without this, you are vulnerable to server-level attacks**

**Required:**
- [ ] Configure Nginx/Apache security headers (see `nginx_security.conf`)
- [ ] Enable HTTPS with valid SSL certificate (Let's Encrypt minimum)
- [ ] Disable HTTP (force HTTPS redirect)
- [ ] Configure WAF (Cloudflare/AWS WAF minimum)
- [ ] Set up DDoS protection
- [ ] Configure firewall rules (UFW/iptables)

**Files Provided:**
- `public/.htaccess` - Apache security hardening
- `nginx_security.conf` - Nginx security configuration

**Commands:**
```bash
# Enable HTTPS (Let's Encrypt)
certbot --nginx -d wallet.yourdomain.com

# Force HTTPS redirect
echo "SSLRedirect true" >> .htaccess

# Configure strict headers
cat nginx_security.conf >> /etc/nginx/conf.d/security.conf
nginx -t && systemctl reload nginx
```

---

### 2. ❌ Key Management (CRITICAL)
**Without this, encryption is useless**

**Current State:**
- Secrets stored in `.env` file (UNSECURE for production)
- No key rotation automation
- No key versioning

**Required:**
- [ ] Move secrets to secure vault (AWS Secrets Manager, HashiCorp Vault, or Azure Key Vault)
- [ ] Enable automatic key rotation (90-day cycle minimum)
- [ ] Implement key versioning
- [ ] Store `SECRETS_ENCRYPTION_KEY` separately from application
- [ ] Restrict database access to application only (no direct user access)

**Implementation:**
```bash
# Generate secure keys
php -r "echo 'APP_KEY=' . bin2hex(random_bytes(32)) . PHP_EOL;"
php -r "echo 'SECRETS_ENCRYPTION_KEY=' . bin2hex(random_bytes(32)) . PHP_EOL;"
php -r "echo 'TOKEN_SIGNING_SECRET=' . bin2hex(random_bytes(32)) . PHP_EOL;"

# Store in secrets manager (AWS example)
aws secretsmanager create-secret \
  --name NepalPay/Production/Secrets \
  --secret-string file://secrets.json

# NEVER commit .env to git
# Add to .gitignore:
.env
*.env
secrets/
data/secrets/
```

---

### 3. ❌ Replay Attack Protection (HIGH)
**Without this, authenticated requests can be replayed**

**Current State:**
- API requests can be intercepted and replayed
- No request timestamp validation
- No nonce tracking

**Required:**
- [ ] Apply migration 007 (`007_replay_protection.sql`) to database
- [ ] Enable request ID validation in API requests
- [ ] Configure clients to send `X-Request-ID` and `X-Request-Timestamp` headers
- [ ] Implement API request signing for critical operations

**Migration:**
```bash
mysql -u root -p wallet < database/migrations/007_replay_protection.sql
```

**Client Implementation:**
```javascript
// All API requests must include:
const requestId = crypto.randomUUID();
const timestamp = Math.floor(Date.now() / 1000);

headers: {
  'X-Request-ID': requestId,
  'X-Request-Timestamp': timestamp,
  'X-API-Signature': generateHMAC(requestId + timestamp + body, secret)
}
```

---

### 4. ❌ Monitoring & Observability (HIGH)
**Without this, you cannot detect or respond to incidents**

**Current State:**
- Logs exist but no real-time monitoring
- No alerting system
- No dashboards

**Required:**
- [ ] Set up log aggregation (ELK stack, Datadog, or New Relic)
- [ ] Configure alerts for critical events:
  - [ ] Fraud blocks (CRITICAL)
  - [ ] Multiple failed authentications (HIGH)
  - [ ] Unusual transaction patterns (HIGH)
  - [ ] Database connection failures (CRITICAL)
  - [ ] Rate limit exhaustion (MEDIUM)
- [ ] Create monitoring dashboards
- [ ] Set up on-call rotation

**Implementation:**
```bash
# Install monitoring stack
docker-compose up -d prometheus grafana loki

# Configure alert rules
cat > alert-rules.yml << EOF
groups:
  - name: security
    rules:
      - alert: CriticalFraudBlock
        expr: fraud_blocks_total{severity="critical"} > 0
        for: 1m
        annotations:
          summary: "Critical fraud detected"
EOF
```

---

### 5. ❌ Backup & Recovery (CRITICAL)
**Without this, data loss is permanent**

**Required:**
- [ ] Configure automated daily database backups
- [ ] Test backup restoration procedure
- [ ] Implement point-in-time recovery (binlog)
- [ ] Store backups in separate geographic location
- [ ] Encrypt backups at rest
- [ ] Test disaster recovery plan

**Implementation:**
```bash
# Daily backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p$MYSQL_PASSWORD wallet | gzip > /backup/wallet_${DATE}.sql.gz

# Encrypt backup
openssl enc -aes-256-cbc -salt -in wallet_${DATE}.sql.gz -out wallet_${DATE}.sql.gz.enc

# Upload to S3
aws s3 cp wallet_${DATE}.sql.gz.enc s3://wallet-backups/

# Test restoration
gunzip < wallet_${DATE}.sql.gz.enc | openssl enc -d -aes-256-cbc | mysql -u root -p wallet
```

---

### 6. ❌ Out-of-Band User Notifications (MEDIUM)
**Without this, users cannot respond to fraudulent activity**

**Required:**
- [ ] Configure SMS gateway (Twilio, AWS SNS, or similar)
- [ ] Configure email service (SendGrid, SES, or Postmark)
- [ ] Implement push notifications (Firebase/Apple Push)
- [ ] Send alerts for:
  - [ ] Login from new device
  - [ ] Large transactions
  - [ ] Account changes
  - [ ] Suspicious activity
- [ ] Allow users to verify transactions via OTP

**Implementation:**
```php
// In SecurityService or AlertService
AlertService::triggerSecurityAlert(
    'login_new_device',
    'New login detected from unknown device',
    [
        'user_id' => $userId,
        'ip' => $ip,
        'device' => $userAgent,
        'location' => $geoLocation,
        'severity' => AlertService::SEVERITY_HIGH
    ]
);

// Send SMS
SmsService::send(
    $user->phone,
    "Security Alert: Login from {$ip}. If not you, contact support immediately."
);
```

---

## 📋 DEPLOYMENT CHECKLIST

### Pre-Deployment (Complete ALL)

- [ ] Infrastructure security configured
- [ ] HTTPS enabled with valid certificate
- [ ] Secrets moved to secure vault
- [ ] Key rotation enabled
- [ ] Database backups configured and tested
- [ ] Migration 007 applied (replay protection)
- [ ] Monitoring and alerting configured
- [ ] SMS/email notification services configured
- [ ] Penetration test completed
- [ ] Security audit passed
- [ ] Disaster recovery plan tested
- [ ] Team trained on incident response

### Deployment Steps

1. **Backup Everything**
   ```bash
   mysqldump -u root -p wallet > wallet_pre_prod_$(date +%Y%m%d).sql
   tar -czf code_backup_$(date +%Y%m%d).tar.gz /xampp/htdocs/wallet/
   ```

2. **Apply Database Migrations**
   ```bash
   mysql -u root -p wallet < database/migrations/007_replay_protection.sql
   ```

3. **Update Configuration**
   ```bash
   # Update .env with production values
   # NEVER commit .env to version control
   ```

4. **Deploy Code**
   ```bash
   rsync -avz --exclude='.env' --exclude='*.md' --exclude='test*' \
     /xampp/htdocs/wallet/ production:/var/www/wallet/
   ```

5. **Configure Web Server**
   ```bash
   # Copy security configs
   cp /xampp/htdocs/wallet/nginx_security.conf /etc/nginx/conf.d/wallet_security.conf
   cp /xampp/htdocs/wallet/public/.htaccess /var/www/wallet/public/.htaccess
   
   # Test and reload
   nginx -t && systemctl reload nginx
   ```

6. **Verify Deployment**
   ```bash
   # Run all tests
   curl http://localhost/wallet/test_security_features.php
   curl http://localhost/wallet/test_db.php
   
   # Check logs
   tail -f /var/log/nginx/error.log
   tail -f /var/log/nginx/access.log
   ```

7. **Monitor First 24 Hours**
   - Check error rates
   - Verify all alerts functioning
   - Monitor transaction success rate
   - Review security event logs

---

## ⚠️ PRODUCTION REQUIREMENTS (DO NOT SKIP)

### Hard Requirements
1. ✅ HTTPS with valid certificate (TLS 1.2+)
2. ✅ Database backups with tested restoration
3. ✅ Secrets in secure vault (NOT in .env files)
4. ✅ Security monitoring with real-time alerts
5. ✅ Penetration test completed
6. ✅ Incident response plan documented
7. ✅ Team trained on security procedures
8. ✅ Audit logging enabled and reviewed

### Soft Requirements (Recommended)
1. ✅ WAF (Web Application Firewall)
2. ✅ DDoS protection
3. ✅ Geographic redundancy
4. ✅ Load balancing
5. ✅ Automated failover
6. ✅ Performance monitoring
7. ✅ User behavior analytics
8. ✅ Automated threat detection

---

## 🎯 CURRENT STATE ASSESSMENT

### What's Complete ✅
- Application security (CSRF, PIN, fraud detection)
- Database encryption for tokens
- Audit logging
- Code-level protections
- Testing frameworks

### What's Missing ❌
- Infrastructure security
- Key management system
- Replay protection (migration pending)
- Monitoring/alerting
- Backup/recovery
- Out-of-band notifications

### Risk Level
- **Application Layer:** LOW (well-secured)
- **Infrastructure Layer:** HIGH (unconfigured)
- **Operational Layer:** CRITICAL (no monitoring)
- **Overall:** **⚠️ NOT READY FOR PRODUCTION**

---

## 📞 INCIDENT RESPONSE

### Immediate Actions (First 5 Minutes)
1. Acknowledge alert
2. Assess scope
3. If critical: Initiate incident response
4. If fraud-related: Notify affected users

### Investigation (Next 30 Minutes)
1. Review security event logs
2. Check for related incidents
3. Identify root cause
4. Document findings

### Mitigation (Next 2 Hours)
1. Block malicious IPs
2. Revoke compromised tokens
3. Implement temporary controls
4. Monitor for recurrence

### Recovery (Next 24 Hours)
1. Restore from backups if needed
2. Implement permanent fixes
3. Update security controls
4. Conduct post-mortem

---

## 🔍 VALIDATION COMMANDS

```bash
# Test HTTPS
curl -I https://wallet.yourdomain.com

# Check security headers
curl -I https://wallet.yourdomain.com | grep -i "strict-transport-security\|x-frame-options\|content-security-policy"

# Test database backup
mysqldump -u root -p wallet | gzip > /tmp/test_backup.sql.gz

# Verify encryption
mysql -u root -p -e "SELECT token_encrypted, token_iv FROM api_tokens LIMIT 1;" wallet

# Check monitoring
curl http://localhost:9090/api/v1/query?query=up

# Test alerting (send test)
curl -X POST http://localhost:3000/api/alerts/test

# Verify migration
mysql -u root -p -e "SHOW TABLES LIKE 'request_nonces';" wallet
```

---

## 📄 COMPLIANCE CHECKLIST

### PCI DSS
- [ ] Requirement 2: Change default passwords
- [ ] Requirement 3: Encrypt stored cardholder data
- [ ] Requirement 4: Encrypt transmission over public networks
- [ ] Requirement 6: Develop secure systems
- [ ] Requirement 8: Identify and authenticate access
- [ ] Requirement 10: Track and monitor access
- [ ] Requirement 11: Regular security testing
- [ ] Requirement 12: Maintain security policy

### PSD2
- [ ] Strong Customer Authentication (SCA)
- [ ] Secure communication
- [ ] Transaction monitoring
- [ ] Incident reporting

### GDPR
- [ ] Data minimization
- [ ] Encryption and pseudonymization
- [ ] Data breach notification
- [ ] Data subject rights
- [ ] Privacy by design

---

## 🚨 LAST WARNING

**DO NOT deploy to production until:**
1. All hard requirements are met
2. Penetration test is clean
3. Team is trained
4. Backup/tested recovery works
5. Monitoring is operational

**The code is secure. The infrastructure is not.**

**Deploy at your own risk.**

---

*Document Version: 1.0*  
*Last Updated: April 25, 2026*  
*Next Review: Before production deployment*
