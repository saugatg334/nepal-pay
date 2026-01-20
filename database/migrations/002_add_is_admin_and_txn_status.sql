-- Migration: add is_admin to users and ensure transactions.status exists
-- Run this against your nepal_pay_simple database

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0;

-- Add a status column to transactions if not present. Use varchar for compatibility.
ALTER TABLE transactions
  ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'completed';

-- Ensure txn_id exists (used for receipts / lookup)
ALTER TABLE transactions
  ADD COLUMN IF NOT EXISTS txn_id VARCHAR(64) DEFAULT NULL;