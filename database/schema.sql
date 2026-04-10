-- Nepal Pay Digital Wallet System
-- ER Diagram Structure and SQL Schema
-- Created for Minor Project EG3107CT

-- ===========================================
-- 1. USER TABLE
-- ===========================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    address TEXT,
    date_of_birth DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_status (status)
);

-- ===========================================
-- 2. WALLET TABLE
-- ===========================================
CREATE TABLE wallets (
    wallet_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0.00,
    wallet_status ENUM('active', 'frozen', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_wallet_status (wallet_status),
    CHECK (balance >= 0)
);

-- ===========================================
-- 3. BANK_ACCOUNTS TABLE
-- ===========================================
CREATE TABLE bank_accounts (
    bank_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    bank_name VARCHAR(50) NOT NULL,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    account_holder_name VARCHAR(100) NOT NULL,
    linked_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'pending_verification') DEFAULT 'pending_verification',
    is_primary BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_account_number (account_number),
    INDEX idx_status (status)
);

-- ===========================================
-- 4. BILLERS TABLE
-- ===========================================
CREATE TABLE billers (
    biller_id INT PRIMARY KEY AUTO_INCREMENT,
    biller_name VARCHAR(100) NOT NULL,
    biller_type ENUM('electricity', 'water', 'internet', 'telephone', 'gas') NOT NULL,
    contact_info VARCHAR(255),
    api_endpoint VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_biller_type (biller_type),
    INDEX idx_status (status)
);

-- ===========================================
-- 5. BILL_PAYMENTS TABLE
-- ===========================================
CREATE TABLE bill_payments (
    bill_payment_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    biller_id INT NOT NULL,
    customer_id VARCHAR(50) NOT NULL, -- SC Number, Customer ID, etc.
    amount DECIMAL(10,2) NOT NULL,
    reference_number VARCHAR(100) UNIQUE,
    bill_date DATE,
    due_date DATE,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    transaction_fee DECIMAL(5,2) DEFAULT 0.00,
    description TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (biller_id) REFERENCES billers(biller_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_biller_id (biller_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_payment_date (payment_date),
    INDEX idx_customer_id (customer_id)
);

-- ===========================================
-- 6. TRANSACTIONS TABLE
-- ===========================================
CREATE TABLE transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    wallet_id INT NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal', 'transfer', 'bill_payment', 'topup', 'refund') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    description TEXT,
    reference_number VARCHAR(100) UNIQUE,
    recipient_wallet_id INT NULL, -- For transfers
    bill_payment_id INT NULL, -- For bill payments
    transaction_fee DECIMAL(5,2) DEFAULT 0.00,
    balance_before DECIMAL(15,2),
    balance_after DECIMAL(15,2),
    FOREIGN KEY (wallet_id) REFERENCES wallets(wallet_id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_wallet_id) REFERENCES wallets(wallet_id) ON DELETE SET NULL,
    FOREIGN KEY (bill_payment_id) REFERENCES bill_payments(bill_payment_id) ON DELETE SET NULL,
    INDEX idx_wallet_id (wallet_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_status (status),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_reference_number (reference_number)
);

-- ===========================================
-- 7. KYC_VERIFICATION TABLE
-- ===========================================
CREATE TABLE kyc_verification (
    kyc_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    document_type ENUM('citizenship', 'passport', 'license') NOT NULL,
    document_number VARCHAR(50) UNIQUE NOT NULL,
    front_image_path VARCHAR(255),
    back_image_path VARCHAR(255),
    selfie_image_path VARCHAR(255),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    verified_by INT NULL, -- Admin user_id
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_document_type (document_type)
);

-- ===========================================
-- 8. ADMIN_USERS TABLE
-- ===========================================
CREATE TABLE admin_users (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'support') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    created_by INT NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- ===========================================
-- 9. ADMIN_INVITES TABLE
-- ===========================================
CREATE TABLE admin_invites (
    invite_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'support') NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    invited_by INT NOT NULL,
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    accepted_at TIMESTAMP NULL,
    status ENUM('pending', 'accepted', 'expired') DEFAULT 'pending',
    FOREIGN KEY (invited_by) REFERENCES admin_users(admin_id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_status (status)
);

-- ===========================================
-- 10. ACTIVITY_LOGS TABLE
-- ===========================================
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    admin_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- ===========================================
-- 11. AUDIT_LOGS TABLE
-- ===========================================
CREATE TABLE audit_logs (
    audit_id INT PRIMARY KEY AUTO_INCREMENT,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values JSON,
    new_values JSON,
    changed_by INT NULL, -- user_id or admin_id
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    INDEX idx_table_name (table_name),
    INDEX idx_record_id (record_id),
    INDEX idx_action (action),
    INDEX idx_changed_at (changed_at)
);

-- ===========================================
-- 12. OTP_LOGS TABLE
-- ===========================================
CREATE TABLE otp_logs (
    otp_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    phone VARCHAR(15) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    purpose ENUM('registration', 'login', 'password_reset', 'transaction') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    attempts INT DEFAULT 0,
    status ENUM('sent', 'verified', 'expired', 'failed') DEFAULT 'sent',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_phone (phone),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
);

-- ===========================================
-- 13. NOTIFICATIONS TABLE
-- ===========================================
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- ===========================================
-- INSERT SAMPLE DATA
-- ===========================================

-- Sample Users
INSERT INTO users (full_name, email, phone, password_hash, address, date_of_birth) VALUES
('John Doe', 'john@example.com', '9800000001', '$2y$10$hashedpassword1', 'Kathmandu, Nepal', '1990-01-01'),
('Jane Smith', 'jane@example.com', '9800000002', '$2y$10$hashedpassword2', 'Pokhara, Nepal', '1992-05-15'),
('Ram Prasad', 'ram@example.com', '9800000003', '$2y$10$hashedpassword3', 'Lalitpur, Nepal', '1988-12-10');

-- Sample Billers
INSERT INTO billers (biller_name, biller_type, contact_info, api_endpoint) VALUES
('Nepal Electricity Authority', 'electricity', 'nea@nea.org.np', 'https://api.nea.gov.np'),
('Kathmandu Upatyaka Khanepani Limited', 'water', 'info@kukl.org.np', 'https://api.kukl.org.np'),
('WorldLink Communications', 'internet', 'info@worldlink.com.np', 'https://api.worldlink.com.np'),
('Ncell', 'telephone', 'info@ncell.com.np', 'https://api.ncell.com.np');

-- Sample Admin
INSERT INTO admin_users (name, email, password_hash, role) VALUES
('Super Admin', 'admin@nepalpay.com', '$2y$10$adminhashedpassword', 'super_admin');

-- ===========================================
-- USEFUL VIEWS FOR REPORTING
-- ===========================================

-- User Wallet Summary View
CREATE VIEW user_wallet_summary AS
SELECT
    u.user_id,
    u.full_name,
    u.email,
    u.phone,
    w.balance,
    w.wallet_status,
    COUNT(t.transaction_id) as total_transactions,
    COALESCE(SUM(CASE WHEN t.transaction_type = 'deposit' THEN t.amount END), 0) as total_deposits,
    COALESCE(SUM(CASE WHEN t.transaction_type = 'bill_payment' THEN t.amount END), 0) as total_bill_payments
FROM users u
LEFT JOIN wallets w ON u.user_id = w.user_id
LEFT JOIN transactions t ON w.wallet_id = t.wallet_id AND t.status = 'completed'
GROUP BY u.user_id, u.full_name, u.email, u.phone, w.balance, w.wallet_status;

-- Transaction Summary View
CREATE VIEW transaction_summary AS
SELECT
    DATE(t.transaction_date) as date,
    t.transaction_type,
    COUNT(*) as count,
    SUM(t.amount) as total_amount,
    AVG(t.amount) as avg_amount
FROM transactions t
WHERE t.status = 'completed'
GROUP BY DATE(t.transaction_date), t.transaction_type
ORDER BY date DESC, total_amount DESC;

-- ===========================================
-- STORED PROCEDURES FOR BUSINESS LOGIC
-- ===========================================

-- Procedure for wallet transfer
DELIMITER //
CREATE PROCEDURE transfer_money(
    IN sender_wallet_id INT,
    IN receiver_wallet_id INT,
    IN transfer_amount DECIMAL(15,2),
    IN description TEXT,
    OUT result_code INT,
    OUT result_message VARCHAR(255)
)
BEGIN
    DECLARE sender_balance DECIMAL(15,2);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET result_code = -1;
        SET result_message = 'Transaction failed due to database error';
    END;

    START TRANSACTION;

    -- Check sender balance
    SELECT balance INTO sender_balance
    FROM wallets
    WHERE wallet_id = sender_wallet_id AND wallet_status = 'active'
    FOR UPDATE;

    IF sender_balance IS NULL THEN
        SET result_code = -2;
        SET result_message = 'Sender wallet not found or inactive';
        ROLLBACK;
    ELSEIF sender_balance < transfer_amount THEN
        SET result_code = -3;
        SET result_message = 'Insufficient balance';
        ROLLBACK;
    ELSE
        -- Deduct from sender
        UPDATE wallets SET balance = balance - transfer_amount
        WHERE wallet_id = sender_wallet_id;

        -- Add to receiver
        UPDATE wallets SET balance = balance + transfer_amount
        WHERE wallet_id = receiver_wallet_id;

        -- Record transactions
        INSERT INTO transactions (wallet_id, transaction_type, amount, status, description, recipient_wallet_id, balance_before, balance_after)
        SELECT sender_wallet_id, 'transfer', transfer_amount, 'completed', description, receiver_wallet_id, sender_balance, sender_balance - transfer_amount;

        INSERT INTO transactions (wallet_id, transaction_type, amount, status, description, balance_before, balance_after)
        SELECT receiver_wallet_id, 'transfer', transfer_amount, 'completed', description,
               (SELECT balance FROM wallets WHERE wallet_id = receiver_wallet_id) - transfer_amount,
               (SELECT balance FROM wallets WHERE wallet_id = receiver_wallet_id);

        SET result_code = 0;
        SET result_message = 'Transfer completed successfully';
        COMMIT;
    END IF;
END //
DELIMITER ;

-- ===========================================
-- TRIGGERS FOR AUDIT LOGGING
-- ===========================================

-- Trigger for users table audit
DELIMITER //
CREATE TRIGGER users_audit_trigger AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (table_name, record_id, action, old_values, new_values, changed_by)
    VALUES ('users', NEW.user_id, 'UPDATE',
            JSON_OBJECT('full_name', OLD.full_name, 'email', OLD.email, 'phone', OLD.phone, 'status', OLD.status),
            JSON_OBJECT('full_name', NEW.full_name, 'email', NEW.email, 'phone', NEW.phone, 'status', NEW.status),
            @current_user_id);
END //
DELIMITER ;

-- ===========================================
-- INDEXES FOR PERFORMANCE
-- ===========================================

-- Additional indexes for better query performance
CREATE INDEX idx_transactions_date_type ON transactions (transaction_date, transaction_type);
CREATE INDEX idx_bill_payments_user_date ON bill_payments (user_id, payment_date);
CREATE INDEX idx_activity_logs_user_date ON activity_logs (user_id, created_at);
CREATE INDEX idx_notifications_user_read ON notifications (user_id, is_read);

-- ===========================================
-- CONSTRAINTS AND VALIDATION
-- ===========================================

-- Ensure wallet balance never goes negative (additional check)
ALTER TABLE wallets ADD CONSTRAINT chk_positive_balance CHECK (balance >= 0);

-- Ensure transaction amounts are positive
ALTER TABLE transactions ADD CONSTRAINT chk_positive_amount CHECK (amount > 0);
ALTER TABLE bill_payments ADD CONSTRAINT chk_positive_bill_amount CHECK (amount > 0);