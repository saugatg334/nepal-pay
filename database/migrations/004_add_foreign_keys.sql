-- NepalPay Wallet Migration 004
-- Add foreign key constraints for data integrity
-- This adds missing FK relationships that were commented out in v1

-- Step 1: Verify data consistency before adding constraints
-- If any orphan rows exist, they will be logged but constraint creation will fail.
-- Fix any orphan data manually before running in production.

-- Add FK: merchant_payments.merchant_id -> merchants.id
ALTER TABLE merchant_payments 
    ADD CONSTRAINT fk_merchant_payments_merchant 
    FOREIGN KEY (merchant_id) REFERENCES merchants(id) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

-- Add FK: merchant_payments.transaction_id -> transactions.id
ALTER TABLE merchant_payments 
    ADD CONSTRAINT fk_merchant_payments_transaction 
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;

-- Additional FKs for referential integrity (if not already present)
-- Ensure balance_adjustments.wallet_id references wallets.id
ALTER TABLE balance_adjustments 
    ADD CONSTRAINT fk_balance_adjustments_wallet 
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

-- Ensure sessions.user_id references users.id (may already exist, check before adding)
-- If the FK already exists, this will fail. Use IF NOT EXISTS logic per MySQL 8.0.19+.
-- For older MySQL, you'll need to check manually.

-- Note: Run `SHOW CREATE TABLE merchant_payments;` to verify if FKs exist before execution.
