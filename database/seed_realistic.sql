-- Realistic Data Seed for Nepal Pay Wallet
-- Run: mysql -u root -p nepalpay < database/seed_realistic.sql

-- Clear existing
TRUNCATE TABLE transactions;
TRUNCATE TABLE withdrawals;
TRUNCATE TABLE deposits;
DELETE FROM users WHERE is_admin = 0;

-- Password hashes: 123456=first, 258036=second
INSERT INTO users (name, phone, password, is_admin, kyc_status, wallet_balance, created_at) VALUES
('Saugat Gautam', '9746587923', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'approved', 15432.50, NOW()),
('Ram Shrestha', '9812345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'approved', 8230.00, NOW()),
('Sita Pandey', '9841234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'pending', 4500.75, NOW()),
('Test Merchant', '9851112223', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'approved', 125000.00, NOW());

-- Admin
INSERT INTO users (name, phone, password, is_admin, kyc_status, wallet_balance) VALUES
('System Admin', '9746587923', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'approved', 1000000.00) 
ON DUPLICATE KEY UPDATE wallet_balance = 1000000.00;

-- Realistic transactions (balance consistent)
INSERT INTO transactions (sender_id, receiver_id, amount, type, status, description, created_at) VALUES
(1,2, 1000, 'transfer', 'completed', 'Sent to Ram', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2,1, 500, 'transfer', 'completed', 'Return payment', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1,NULL, 2000, 'deposit', 'completed', 'Bank deposit', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3,NULL, 5000, 'bill_payment', 'completed', 'NEA Electricity', DATE_SUB(NOW(), INTERVAL 1 HOUR));

-- Pending deposit/withdrawal for admin approval
INSERT INTO deposits (user_id, amount, status, reference, created_at) VALUES 
(1, 10000, 'pending', 'DEP-001', NOW());

INSERT INTO withdrawals (user_id, amount, status, bank_account, created_at) VALUES 
(2, 3000, 'pending', '1234567890', NOW());

SELECT 'Seeding complete - 4 users, 4 txns, 1 deposit, 1 withdrawal' as status;
SELECT * FROM users LIMIT 5;
SELECT COUNT(*) as transaction_count FROM transactions;
