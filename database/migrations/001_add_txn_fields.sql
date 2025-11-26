-- Migration: add transaction metadata columns
ALTER TABLE transactions
  ADD COLUMN txn_id VARCHAR(64) UNIQUE NULL,
  ADD COLUMN provider VARCHAR(100) NULL,
  ADD COLUMN customer_ref VARCHAR(150) NULL,
  ADD COLUMN method VARCHAR(50) NULL,
  ADD COLUMN provider_response TEXT NULL;

-- Optional: add index on txn_id
CREATE INDEX idx_transactions_txn_id ON transactions(txn_id);
