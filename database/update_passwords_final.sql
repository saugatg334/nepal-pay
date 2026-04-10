-- Update passwords for NepalPay (saugat DB)
-- Run AFTER main saugat_wallet_complete.sql
-- User phone login: 9746587923 / 123456
-- Admin email login: admin@nepalpay.com / 258036

USE saugat;

-- Update User password to 123456
UPDATE users 
SET password = '$2y$10$u1HkYyH5u7nQ6R0Y6lJk5eGZz8Fz4JkP3cQmXb9Vv8uQwYg9x6K8W',
    full_name = 'Saugat User',
    balance = 1500.00
WHERE phone = '9746587923';

-- Update Admin password to 258036 (create if missing)
INSERT IGNORE INTO users (full_name, phone, email, password, role, balance) 
VALUES ('Admin User', '9810000001', 'admin@nepalpay.com', '$2y$10$K9dL3gYzX7aQ1pV0nB8uQeF6xR9yT4mZcW8kYp6Vx2b3N4Q5r6T7u', 'admin', 10000.00);

UPDATE users 
SET password = '$2y$10$K9dL3gYzX7aQ1pV0nB8uQeF6xR9yT4mZcW8kYp6Vx2b3N4Q5r6T7u'
WHERE email = 'admin@nepalpay.com';

-- Verify
SELECT phone, email, role, balance FROM users WHERE phone='9746587923' OR email='admin@nepalpay.com';

