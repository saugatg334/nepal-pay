# NepalPay V1 Production Safety Guide

## 🎯 Overview

This guide implements **financial correctness** guardrails for the NepalPay wallet system. The implementation focuses on:

1. **Atomic Transactions** - Prevent race conditions and double-spending
2. **Idempotency** - Prevent duplicate execution on retries
3. **Reconciliation** - Detect balance inconsistencies automatically
4. **Structured Logging** - Complete financial audit trail

---

## 🔧 Core Components Implemented

### 1. **IdempotencyService** (`app/Services/IdempotencyService.php`)

**Problem:** Client retries requests on timeout. Without idempotency, each retry executes the transaction again → DOUBLE SPENDING.

**Solution:** Cache request + response. On retry with same key, return cached result instead of re-executing.

**How It Works:**
```
Client sends: POST /api/transfer with X-Idempotency-Key: abc123
Server: Checks cache, not found. Executes transfer. Caches response.
Client retries (network glitch):  POST /api/transfer with X-Idempotency-Key: abc123
Server: Checks cache, FOUND. Returns same response immediately. No second transfer!
```

**Key Methods:**
- `checkCache()` - Check if request has been processed before
- `markPending()` - Mark request as being processed
- `cacheSuccess()` - Store successful response
- `cacheError()` - Store error response
- `cleanup()` - Delete expired entries (run daily via cron)

**Implementation:** 
```php
// In your controller
$idempotencyKey = $_SERVER['HTTP_X_IDEMPOTENCY_KEY'];

// Check cache first
$cached = IdempotencyService::checkCache(
    $userId, 
    $idempotencyKey, 
    '/api/transfer',
    json_encode($requestData)
);
if ($cached) {
    return $cached;  // Return immediately
}

// Mark pending
IdempotencyService::markPending(
    $userId,
    $idempotencyKey,
    '/api/transfer',
    json_encode($requestData)
);

// Execute transfer
$result = AtomicTransferService::transfer(...);

// Cache success
IdempotencyService::cacheSuccess(
    $userId,
    $idempotencyKey,
    '/api/transfer',
    json_encode($requestData),
    200,
    json_encode($result)
);
```

---

### 2. **AtomicTransferService** (`app/Services/AtomicTransferService.php`)

**Problem:** Concurrent transfers from same wallet can both debit before either credits, resulting in NEGATIVE BALANCE.

**Solution:** Use `SELECT...FOR UPDATE` to lock wallet rows. Only one transfer at a time can modify a wallet.

**How It Works:**
```sql
-- Lock sender wallet (other transactions wait)
SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE;

-- Check balance (must be after lock acquired)
IF balance < amount THEN FAIL;

-- Debit sender
UPDATE wallets SET balance = balance - ? WHERE id = ?;

-- Credit receiver
UPDATE wallets SET balance = balance + ? WHERE id = ?;

-- Record transaction (immutable)
INSERT INTO transactions ...;

-- Commit or Rollback (all or nothing)
COMMIT / ROLLBACK;
```

**Real-World Example:**
- User has 100 NPR
- Sends 50 NPR to Alice AND 50 NPR to Bob simultaneously
- **Without locking:** Both requests see balance=100, both debit, balance becomes -50
- **With locking:** First request locks wallet, second request waits, only one debit executes

**Key Methods:**
- `transfer()` - Execute atomic transfer with row locking

**Implementation:**
```php
$result = AtomicTransferService::transfer(
    fromUserId: $userId,
    toWalletNumber: "NP987654",
    amount: 5000.00,
    description: "Rent payment",
    idempotencyKey: "user-generated-uuid"
);

// Returns: ['transaction_id' => '...', 'new_balance' => ..., ...]
```

---

### 3. **ReconciliationService** (`app/Services/ReconciliationService.php`)

**Problem:** Database corruption, code bugs, or external interference could cause wallet balance to diverge from transaction history. Without detection, discrepancy compounds until system is insolvent.

**Solution:** Nightly job compares each wallet's balance vs. sum of transactions. Alert on mismatch.

**How It Works:**
```
FOR each wallet:
  expected_balance = SUM(all credits) - SUM(all debits)
  actual_balance = SELECT balance FROM wallets
  
  IF expected_balance != actual_balance:
    Log CRITICAL alert
    Flag account for manual review
```

**Mathematical Verification:**
```
Credits (additions):
  - receive: Money from other users
  - add_money: KYC deposits
  - refund: Reversed transactions

Debits (withdrawals):
  - send: Money to other users
  - withdraw: Cash withdrawal
  - fee: Service charges
  - bill_payment: Utility payments

Expected Balance = Initial + All Credits - All Debits
```

**Key Methods:**
- `reconcileAllWallets()` - Run reconciliation for all wallets (nightly)
- `reconcileWallet()` - Check single wallet
- `calculateExpectedBalance()` - Calculate from transaction history
- `getWalletStatus()` - Get detailed status report
- `manuallyReconcile()` - Admin function to fix mismatches

**Setup (Cron Job):**
```bash
# Add to crontab (runs at 2 AM daily)
0 2 * * * php /var/www/wallet/cron/reconcile_balances.php

# Or run manually:
php -r "require 'app/bootstrap.php'; 
        ReconciliationService::reconcileAllWallets();"
```

**Example Output:**
```
Reconciliation completed:
- Total wallets checked: 1,234
- Mismatches found: 2 (ALERT!)
- Start: 2024-04-25 02:00:00
- End: 2024-04-25 02:15:30

Mismatches:
  - User 456: Expected 10,000 NPR, Actual 9,999 NPR (Off by 1 cent - OK)
  - User 789: Expected 5,000 NPR, Actual 4,500 NPR (Off by 500 - INVESTIGATE)
```

---

### 4. **StructuredLogger** (`app/Services/StructuredLogger.php`)

**Problem:** Financial transactions need complete audit trail for compliance (PCI-DSS, PSD2, GDPR).

**Solution:** Log all transactions in structured JSON format (one line per transaction). Machine-readable and queryable by ELK/Splunk/Datadog.

**What Gets Logged:**
```json
{
  "timestamp": "2024-04-25T10:30:45Z",
  "user_id": 123,
  "transaction_id": "TXN-20240425-abc123",
  "action": "transfer",
  "amount": 5000.00,
  "fee": 0.00,
  "recipient_id": 456,
  "balance_before": 10000.00,
  "balance_after": 4999.00,
  "status": "success",
  "ip": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "description": "Rent payment",
  "source": "api"
}
```

**Key Methods:**
- `logTransaction()` - Log generic transaction
- `logTransfer()` - Log user-to-user transfer
- `logAddMoney()` - Log deposit
- `logFailure()` - Log failed transaction
- `getRecentTransactions()` - Query logs (for basic analysis)

**Implementation:**
```php
StructuredLogger::logTransfer(
    transactionId: 'TXN-20240425-abc123',
    senderId: 123,
    recipientId: 456,
    amount: 5000.00,
    fee: 0.00,
    senderBalanceBefore: 10000.00,
    senderBalanceAfter: 4999.00,
    description: 'Rent payment'
);
```

**Log File Format:**
```
Location: /var/www/wallet/logs/transactions.jsonl (set via env)
Format: JSONL (JSON Lines - one JSON object per line)
Retention: Append-only (never delete, only archive)
```

---

## 📊 Database Schema Additions

### Migration 008: Idempotency Keys Table

```sql
CREATE TABLE idempotency_keys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    idempotency_key VARCHAR(255) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    request_hash VARCHAR(64) NOT NULL,
    response_code INT DEFAULT NULL,
    response_body LONGTEXT DEFAULT NULL,
    response_headers JSON DEFAULT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT (DATE_ADD(NOW(), INTERVAL 24 HOUR)),
    
    UNIQUE KEY unique_idempotency (user_id, idempotency_key),
    INDEX idx_idempotency_key (idempotency_key),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Cleanup Script (run daily):**
```bash
# Executed via cron
DELETE FROM idempotency_keys WHERE expires_at < NOW();
```

### Migration 009: Reconciliation Log Tables

```sql
CREATE TABLE reconciliation_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    wallet_id INT NOT NULL,
    user_id INT NOT NULL,
    expected_balance DECIMAL(15, 2) NOT NULL,
    actual_balance DECIMAL(15, 2) NOT NULL,
    difference DECIMAL(15, 2) NOT NULL,
    transaction_count INT DEFAULT 0,
    is_reconciled TINYINT(1) DEFAULT 0,
    reconciliation_note TEXT DEFAULT NULL,
    checked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reconciled_at DATETIME DEFAULT NULL,
    
    INDEX idx_wallet_id (wallet_id),
    INDEX idx_is_reconciled (is_reconciled),
    INDEX idx_mismatch (difference),
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE transaction_audit_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    transaction_id VARCHAR(50) NOT NULL,
    user_id INT,
    action_type ENUM(
        'balance_check', 'transfer_initiated', 'transfer_committed',
        'transfer_failed', 'add_money', 'withdrawal', 'refund', 'adjustment'
    ) NOT NULL,
    amount DECIMAL(15, 2) DEFAULT NULL,
    balance_before DECIMAL(15, 2) DEFAULT NULL,
    balance_after DECIMAL(15, 2) DEFAULT NULL,
    details JSON DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🚀 Implementation Checklist

### Phase 1: Apply Migrations
- [ ] Run migration 008 (idempotency_keys table)
  ```bash
  mysql -u root -p wallet < database/migrations/008_idempotency_keys.sql
  ```
- [ ] Run migration 009 (reconciliation tables)
  ```bash
  mysql -u root -p wallet < database/migrations/009_reconciliation_log.sql
  ```

### Phase 2: Update Controllers

For each endpoint that modifies data (POST/PUT):

1. **Require X-Idempotency-Key header**
   ```php
   $idempotencyKey = $_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? null;
   if (!$idempotencyKey || !IdempotencyService::validateKey($idempotencyKey)) {
       return $this->jsonResponse(['error' => 'Missing/invalid X-Idempotency-Key'], 400);
   }
   ```

2. **Use AtomicTransferService for transfers**
   ```php
   $result = AtomicTransferService::transfer(
       fromUserId: $userId,
       toWalletNumber: $recipient,
       amount: $amount,
       description: $description,
       idempotencyKey: $idempotencyKey
   );
   ```

3. **Log transactions via StructuredLogger**
   ```php
   StructuredLogger::logTransfer(
       transactionId: $result['transaction_id'],
       senderId: $userId,
       recipientId: $recipientUserId,
       amount: $amount,
       fee: 0.00,
       senderBalanceBefore: $oldBalance,
       senderBalanceAfter: $result['new_balance'],
       description: $description
   );
   ```

### Phase 3: Setup Cron Jobs

```bash
# 1. Clean expired idempotency keys (daily at 3 AM)
0 3 * * * php -r "require '/var/www/wallet/app/bootstrap.php'; IdempotencyService::cleanup();"

# 2. Reconcile all wallets (daily at 2 AM)
0 2 * * * php -r "require '/var/www/wallet/app/bootstrap.php'; ReconciliationService::reconcileAllWallets();"

# 3. Archive old logs (weekly)
0 4 * * 0 tar -czf /backup/logs_$(date +\%Y\%m\%d).tar.gz /var/www/wallet/logs/*.jsonl
```

### Phase 4: Testing

**Test Idempotency:**
```bash
# First request
curl -X POST http://localhost/api/transfer \
  -H "X-Idempotency-Key: abc123" \
  -H "Authorization: Bearer token" \
  -d '{"toWalletNumber":"NP123","amount":100}'

# Response: {"transaction_id":"TXN-...", "balance":900}

# Retry with same key
curl -X POST http://localhost/api/transfer \
  -H "X-Idempotency-Key: abc123" \
  -H "Authorization: Bearer token" \
  -d '{"toWalletNumber":"NP123","amount":100}'

# Response: {"transaction_id":"TXN-...", "balance":900} <- SAME RESULT
```

**Test Atomic Transfer:**
```bash
# Concurrent transfers from same wallet (should only execute one)
for i in {1..5}; do
  curl -X POST http://localhost/api/transfer \
    -H "X-Idempotency-Key: transfer-$i" \
    -H "Authorization: Bearer token" \
    -d '{"toWalletNumber":"NP123","amount":10}' &
done
wait

# Check balance - should only be deducted once per successful transfer
```

**Test Reconciliation:**
```php
// Manually run reconciliation
$result = ReconciliationService::reconcileAllWallets();
echo json_encode($result, JSON_PRETTY_PRINT);

// Should output:
// {
//   "total_checked": 150,
//   "mismatches_found": 0,
//   "errors": [],
//   "start_time": "2024-04-25 02:00:00",
//   "end_time": "2024-04-25 02:05:30"
// }
```

---

## 📋 Real-World Scenarios Prevented

### Scenario 1: Race Condition Double-Spend
**Before:** 
- User has 100 NPR
- Sends 100 NPR to Alice AND Bob simultaneously
- Result: Balance becomes -100 NPR ❌

**After:**
- First transfer locks wallet row
- Second transfer waits for lock
- Only one transfer executes
- Result: Balance correctly remains 0 NPR ✅

### Scenario 2: Network Glitch Duplicate
**Before:**
- Transfer executes successfully
- Response lost in network
- Client retries (thinking it failed)
- Result: Money transferred twice ❌

**After:**
- First request caches response
- Retry with same idempotency key
- Returns cached response immediately
- Result: Money transferred once ✅

### Scenario 3: Database Corruption Detection
**Before:**
- Bug causes balance update to fail silently
- Transaction record created but balance not updated
- Balance stays 100 NPR, but user sent 50 NPR
- System is insolvent, no way to detect ❌

**After:**
- Nightly reconciliation runs
- Calculates expected balance from transactions
- Finds mismatch: expected 50, actual 100
- Critical alert sent to operations team ✅

### Scenario 4: Audit Trail for Compliance
**Before:**
- Transaction happens but no structured log
- Compliance audit asks: "Who sent what to whom when?"
- Answer: "No record" ❌

**After:**
- Every transaction logged in JSON format
- Can query: "All transfers > 10,000 NPR"
- Can export for compliance: "All transactions for user 456"
- Complete audit trail available ✅

---

## 🔐 Security Features Enabled

| Feature | Mechanism | Prevents |
|---------|-----------|----------|
| **Atomicity** | BEGIN/COMMIT/ROLLBACK | Partial updates, inconsistent state |
| **Row Locking** | SELECT...FOR UPDATE | Race conditions, double-spending |
| **Idempotency** | Request deduplication | Duplicate transaction execution |
| **Immutability** | Transactions never updated | False audit trails, backdating |
| **Reconciliation** | Balance verification | Undetected data corruption |
| **Structured Logs** | JSON audit trail | Compliance failures, fraud investigation |
| **Authorization** | User ownership checks | IDOR, unauthorized access |
| **Input Validation** | Type & range checks | Injection attacks, invalid data |

---

## 📚 API Documentation

### Header Requirements

**All state-changing requests (POST/PUT/DELETE) must include:**
```
X-Idempotency-Key: {UUID-like-string}
Authorization: Bearer {token}
Content-Type: application/json
```

### Example Endpoint: POST /api/wallet/transfer

**Request:**
```json
POST /api/wallet/transfer HTTP/1.1
Host: wallet.example.com
Content-Type: application/json
Authorization: Bearer eyJhbGc...
X-Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000

{
  "toWalletNumber": "NP987654",
  "amount": 5000.00,
  "description": "Rent payment"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "transaction_id": "TXN-20240425-abc123xyz",
  "amount": 5000.00,
  "recipient_wallet": "NP987654",
  "new_balance": 4999.00,
  "timestamp": "2024-04-25T10:30:45Z"
}
```

**Response (Failure - 400):**
```json
{
  "success": false,
  "error": "Insufficient balance",
  "transaction_id": null
}
```

**Response (Retry - 200):**
```json
{
  "success": true,
  "transaction_id": "TXN-20240425-abc123xyz",
  "amount": 5000.00,
  "recipient_wallet": "NP987654",
  "new_balance": 4999.00,
  "timestamp": "2024-04-25T10:30:45Z"
}
← SAME RESULT as original request
```

---

## 🐛 Troubleshooting

### Problem: Idempotency key being rejected
**Solution:** Ensure key is 32-128 characters, alphanumeric + dashes/underscores
```php
// Valid keys:
"550e8400-e29b-41d4-a716-446655440000"
"user_123_transfer_2024_04_25_123456"

// Invalid keys:
"abc"  // Too short
"abc@#$%"  // Invalid characters
```

### Problem: Reconciliation showing mismatches
**Solution:** Check if transaction records are being updated after creation
```sql
-- Transactions should NEVER be updated
-- If you find any UPDATE statements on transactions table, REMOVE them

-- Good: Append-only
INSERT INTO transactions (...) VALUES (...);

-- Bad: DO NOT DO THIS
UPDATE transactions SET status = 'completed' WHERE id = ?;
```

### Problem: Row locks causing timeouts
**Solution:** Ensure transactions complete quickly
```php
// Good: Fast operations within transaction
Database::beginTransaction();
$wallet = Database::fetch("SELECT ... FOR UPDATE");
Database::query("UPDATE wallets ...");
Database::commit();  // Quick

// Bad: Slow operations within transaction
Database::beginTransaction();
$wallet = Database::fetch("SELECT ... FOR UPDATE");
sleep(10);  // DON'T DO THIS
$apiCall = externalAPI();
Database::query("UPDATE wallets ...");
Database::commit();
```

---

## 📞 Support & Escalation

| Issue | Action |
|-------|--------|
| **Reconciliation mismatch** | Alert operations team immediately → manual review |
| **Idempotency cache hit rate < 5%** | Investigate client retry behavior |
| **Row lock timeouts** | Optimize transaction speed, reduce data operations |
| **Structured logs growing > 1GB/day** | Archive and analyze transaction volume |
| **Cascade of reconciliation failures** | HALT ALL TRANSACTIONS, investigate root cause |

---

## ✅ Production Readiness Checklist

Before deploying to production:

- [ ] All migrations applied successfully
- [ ] Cron jobs configured and tested
- [ ] Reconciliation running nightly with 0 errors
- [ ] Structured logs being written to proper location
- [ ] All controllers updated to require X-Idempotency-Key
- [ ] AtomicTransferService used for all transfers
- [ ] No UPDATE statements on transactions table
- [ ] Monitoring/alerting configured for critical logs
- [ ] Backup and restore procedure tested
- [ ] Team trained on manual reconciliation process

---

*Document Version: 1.0*  
*Created: 2024-04-25*  
*Last Updated: 2024-04-25*
