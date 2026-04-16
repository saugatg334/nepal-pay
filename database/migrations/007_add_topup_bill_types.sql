-- Add Topup and Bill types to transactions table
-- Run this migration to enable topup and bill payment features

ALTER TABLE transactions 
MODIFY COLUMN type ENUM('deposit','transfer','withdrawal','government_payment','bill_payment','bank_payment','topup','bill') NOT NULL;

-- Add index for faster topup queries
CREATE INDEX idx_topup_type ON transactions(type);

-- Add index for bill type queries  
CREATE INDEX idx_bill_type ON transactions(type);