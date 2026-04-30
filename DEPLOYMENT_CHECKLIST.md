# Deployment Checklist: NepalPay Wallet Security Implementation

## Pre-Deployment Verification

### ✅ Code Implementation (Complete)
- [x] CSRF protection for admin actions
- [x] Transaction PIN with lockout
- [x] Fraud detection engine
- [x] Transaction rate limiting
- [x] API token encryption at rest
- [x] Admin audit trail

### ✅ Files Created (8 files)
```
app/Services/FraudDetectionService.php          11,938 bytes
app/Services/TokenEncryptionService.php          9,117 bytes
database/migrations/006_fraud_detection.sql      1,904 bytes
SECURITY_IMPROVEMENTS.md                        11,633 bytes
IMPLEMENTATION_SUMMARY.md                       13,402 bytes
test_security_features.php                       6,855 bytes
syntax_check.php                                 3,711 bytes
README_SECURITY.md                               8,744 bytes
```

### ✅ Files Modified (5 files)
```
app/controller/AdminController.php               12,015 bytes
app/controller/ApiWalletController.php           4,676 bytes
app/controller/ApiAuthController.php             9,521 bytes
app/controller/ApiController.php                 6,802 bytes
app/controller/WalletController.php              7,620 bytes
```

### ✅ Services Enhanced (3 files)
```
app/Services/WalletService.php                  14,199 bytes
app/Services/TransactionPinService.php           5,979 bytes
app/Services/SecurityService.php                 4,136 bytes
```

### ✅ Database Migrations Modified (1 file)
```
database/migrations/002_add_rate_limits_and_api_tokens.sql  3,089 bytes
```

## Deployment Steps

### Phase 1: Preparation (15 minutes)
1. **Backup Current System**
   ```bash
   mysqldump -u root -p wallet > wallet_backup_$(date +%Y%m%d_%H%M%S).sql
   tar -czf wallet_code_backup.tar.gz /xampp/htdocs/wallet/
   ```

2. **Verify Backup Integrity**
   ```bash
   mysql -u root -p -e "SELECT COUNT(*) FROM wallet.transactions;"
   ```

### Phase 2: Database Migration (10 minutes)
3. **Apply Migration 005 (if not applied)**
   ```bash
   mysql -u root -p wallet < 005_transaction_pin.sql
   ```

4. **Apply Migration 006 (fraud detection)**
   ```bash
   mysql -u root -p wallet < 006_fraud_detection.sql
   ```

5. **Update Migration 002 (token encryption)**
   ```bash
   mysql -u root -p wallet < 002_add_rate_limits_and_api_tokens.sql
   ```

6. **Verify Tables Created**
   ```sql
   SHOW TABLES LIKE 'fraud_assessments';
   SHOW TABLES LIKE 'blacklisted_ips';
   SHOW TABLES LIKE 'transaction_limits';
   ```

### Phase 3: Configuration (10 minutes)
7. **Update .env File**
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=<generate-32-byte-key>
   ENCRYPTION_KEY_SALT=<generate-unique-salt>
   
   # Transaction Limits
   TRANSACTION_DAILY_LIMIT=50000
   TRANSACTION_SINGLE_LIMIT=25000
   FRAUD_MAX_DAILY_AMOUNT=50000
   FRAUD_MAX_SINGLE_AMOUNT=25000
   
   # Security
   SESSION_SECURE=true
   SESSION_SAMESITE=Strict
   ```

8. **Generate Encryption Key**
   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   # Copy output to APP_KEY in .env
   
   php -r "echo bin2hex(random_bytes(16));"
   # Copy output to ENCRYPTION_KEY_SALT
   ```

### Phase 4: Code Deployment (5 minutes)
9. **Deploy Modified Files**
   ```bash
   # Copy all modified/new files to production
   rsync -avz app/Services/FraudDetectionService.php production:/path/app/Services/
   rsync -avz app/Services/TokenEncryptionService.php production:/path/app/Services/
   # ... (repeat for all modified files)
   ```

10. **Verify File Permissions**
    ```bash
    chown -R www-data:www-data /path/to/wallet
    chmod -R 755 /path/to/wallet/app
    ```

### Phase 5: Verification (15 minutes)
11. **Run Syntax Check**
    ```bash
    php -l app/Services/FraudDetectionService.php
    php -l app/Services/TokenEncryptionService.php
    # ... (check all modified PHP files)
    ```

12. **Run Feature Verification**
    ```bash
    curl http://localhost/wallet/test_security_features.php
    # Verify all 14 checks pass
    ```

13. **Test Database Connection**
    ```bash
    curl http://localhost/wallet/test_db.php
    # Verify all database tests pass
    ```

14. **Verify Encryption Configuration**
    ```bash
    curl http://localhost/wallet/test_security_features.php | grep -A5 "Encryption"
    # Should show "Token Encryption Service: ✅"
    ```

### Phase 6: Smoke Testing (20 minutes)
15. **Test User Login**
    - Log in with test account
    - Verify session created
    - Check CSRF token in forms

16. **Test Transaction PIN**
    - Attempt transfer (should require PIN)
    - Enter correct PIN (should succeed)
    - Enter wrong PIN 3 times (should lock)

17. **Test Fraud Detection**
    - Make rapid transactions (should flag)
    - Exceed daily limit (should block)
    - Transfer to new recipient (should flag)

18. **Test Admin Functions**
    - Try freeze user (should require CSRF)
    - Submit with CSRF (should succeed)
    - Check audit log for entry

19. **Test API Endpoints**
    - Login via API (should get encrypted token)
    - Make API request (should validate token)
    - Check token is encrypted in DB

20. **Verify Audit Logs**
    ```sql
    SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 10;
    SELECT * FROM fraud_assessments ORDER BY assessed_at DESC LIMIT 10;
    ```

## Post-Deployment Monitoring (First 72 Hours)

### Critical Alerts (Page Immediately)
- [ ] Any "CRITICAL" fraud blocks
- [ ] Token decryption errors
- [ ] CSRF violation attempts
- [ ] Database connection failures

### Warning Alerts (Email Within 1 Hour)
- [ ] >5 fraud blocks per hour
- [ ] >10 PIN lockouts per hour
- [ ] >50% API errors
- [ ] High memory/CPU usage

### Daily Review
- [ ] Fraud detection statistics
- [ ] Transaction limits triggered
- [ ] Failed authentication attempts
- [ ] Audit log entries reviewed

## Rollback Plan

### If Critical Issues (>50% error rate)
1. **Immediate Rollback** (5 minutes)
   ```bash
   # Restore database
   mysql -u root -p wallet < wallet_backup_YYYYMMDD.sql
   
   # Restore previous code
   rsync -avz wallet_backup/ production:/path/
   
   # Restart services
   systemctl restart apache2
   ```

2. **Verify Rollback** (10 minutes)
   - Confirm login works
   - Confirm basic transactions work
   - Confirm no security errors

3. **Analyze Issue**
   - Review error logs
   - Test in staging environment
   - Fix identified problems
   - Retry deployment

### If Partial Issues (<10% error rate)
1. **Disable Specific Features**
   ```php
   // In config
   'fraud_detection_enabled' => false,
   'token_encryption_enabled' => false,
   ```

2. **Investigate**
   - Check error logs
   - Review implementation
   - Fix in staging
   - Retry

## Success Criteria

### All Must Pass (0 Failures Allowed)
- [ ] User login works
- [ ] User logout works
- [ ] Wallet balance displays
- [ ] Money transfer works (with PIN)
- [ ] Transaction history displays
- [ ] Admin functions protected (CSRF)
- [ ] Database tables exist
- [ ] No PHP errors in logs
- [ ] No JavaScript errors in console
- [ ] API endpoints respond correctly

### Performance Criteria (<100ms increase)
- [ ] Page load time <3s
- [ ] API response time <500ms
- [ ] Transaction processing <2s
- [ ] Login process <2s

## Emergency Contacts

| Role | Name | Phone | Email |
|------|------|-------|-------|
| Lead Developer | [Name] | [Phone] | [Email] |
| DevOps Engineer | [Name] | [Phone] | [Email] |
| Security Lead | [Name] | [Phone] | [Email] |
| Database Admin | [Name] | [Phone] | [Email] |

## Documentation Links

- **Security Improvements:** `SECURITY_IMPROVEMENTS.md`
- **Implementation Details:** `IMPLEMENTATION_SUMMARY.md`
- **Test Results:** `test_security_features.php`
- **Quick Reference:** `README_SECURITY.md`

## Sign-Off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Lead Developer | | | |
| DevOps Engineer | | | |
| Security Lead | | | |
| QA Engineer | | | |
| Product Owner | | | |

---

## Notes

- **Estimated Deployment Time:** 1-2 hours
- **Estimated Verification Time:** 1 hour
- **Total Downtime:** <10 minutes (database migration only)
- **Risk Level:** Medium (well-tested, gradual rollout recommended)
- **Recommendation:** Deploy during low-traffic hours

## Support

For deployment issues:
1. Check error logs: `/var/log/apache2/error.log`
2. Run diagnostic: `test_security_features.php`
3. Review documentation: `SECURITY_IMPROVEMENTS.md`
4. Contact lead developer

---

**Document Version:** 1.0  
**Last Updated:** April 25, 2026  
**Next Review:** May 25, 2026