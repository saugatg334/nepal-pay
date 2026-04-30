-- Reconciliation Log Table
-- Daily balance verification audit trail
-- Compares wallet balances with sum of transactions to detect data corruption

CREATE TABLE IF NOT EXISTS reconciliation_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    wallet_id INT NOT NULL,
    user_id INT NOT NULL,
    expected_balance DECIMAL(15, 2) NOT NULL,  -- Sum of all transactions
    actual_balance DECIMAL(15, 2) NOT NULL,    -- Current balance in wallet
    difference DECIMAL(15, 2) NOT NULL,        -- expected - actual
    transaction_count INT DEFAULT 0,           -- Total transactions
    is_reconciled TINYINT(1) DEFAULT 0,        -- Was mismatch resolved?
    reconciliation_note TEXT DEFAULT NULL,     -- Manual note from admin
    checked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reconciled_at DATETIME DEFAULT NULL,
    
    INDEX idx_wallet_id (wallet_id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_reconciled (is_reconciled),
    INDEX idx_checked_at (checked_at),
    INDEX idx_mismatch (difference),  -- Find mismatches
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transaction Audit Trail Table (Immutable)
-- Append-only log of every financial operation
-- Never updated or deleted - pure audit trail

CREATE TABLE IF NOT EXISTS transaction_audit_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    transaction_id VARCHAR(50) NOT NULL,
    user_id INT,
    action_type ENUM(
        'balance_check',
        'transfer_initiated',
        'transfer_committed', 
        'transfer_failed',
        'add_money',
        'withdrawal',
        'refund',
        'adjustment'
    ) NOT NULL,
    amount DECIMAL(15, 2) DEFAULT NULL,
    balance_before DECIMAL(15, 2) DEFAULT NULL,
    balance_after DECIMAL(15, 2) DEFAULT NULL,
    details JSON DEFAULT NULL,  -- {ip, user_agent, reason, etc}
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
