-- NepalPay Wallet Migration 005
-- Transaction PIN for sensitive operations
-- Allows users to set a 4-digit or 6-digit PIN for authorizing payments
-- PIN is hashed using bcrypt (same as passwords)

ALTER TABLE users 
    ADD COLUMN transaction_pin VARCHAR(255) DEFAULT NULL COMMENT 'Hashed transaction PIN',
    ADD COLUMN pin_attempts TINYINT DEFAULT 0 COMMENT 'Failed PIN attempts',
    ADD COLUMN pin_locked_until DATETIME DEFAULT NULL COMMENT 'Lockout until timestamp',
    ADD INDEX idx_pin_locked (pin_locked_until);
