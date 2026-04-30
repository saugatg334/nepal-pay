-- NepalPay v2.0 — Growth & Gamification Schema
-- Run AFTER all previous migrations (001-011)

-- ============================================
-- 1. REFERRAL SYSTEM
-- ============================================
CREATE TABLE IF NOT EXISTS referrals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referrer_id INT NOT NULL COMMENT 'User who invited',
    referee_id INT NULL COMMENT 'User who signed up (null until they register)',
    referral_code VARCHAR(20) UNIQUE NOT NULL,
    reward_amount DECIMAL(10,2) DEFAULT 50.00 COMMENT 'NPR reward amount',
    status ENUM('pending', 'completed', 'expired') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    expires_at DATETIME DEFAULT (CURRENT_TIMESTAMP + INTERVAL 30 DAY),
    INDEX idx_referrer (referrer_id),
    INDEX idx_referee (referee_id),
    INDEX idx_code (referral_code),
    INDEX idx_status (status),
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referee_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Referral tracking for viral growth';

-- ============================================
-- 2. CASHBACK & REWARDS
-- ============================================
CREATE TABLE IF NOT EXISTS cashback_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    original_transaction_id VARCHAR(50) NOT NULL COMMENT 'Linked to main transaction',
    amount DECIMAL(10,2) NOT NULL COMMENT 'Cashback amount earned',
    type ENUM('bill_payment', 'merchant', 'referral', 'streak', 'loyalty_bonus') NOT NULL,
    status ENUM('pending', 'credited', 'redeemed', 'expired') DEFAULT 'pending',
    description VARCHAR(255),
    expires_at DATETIME DEFAULT (CURRENT_TIMESTAMP + INTERVAL 90 DAY),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    credited_at DATETIME NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cashback wallet for user rewards';

-- ============================================
-- 3. LOYALTY TIERS
-- ============================================
CREATE TABLE IF NOT EXISTS loyalty_tiers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    current_tier ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    points INT DEFAULT 0,
    total_transactions INT DEFAULT 0,
    monthly_volume DECIMAL(15,2) DEFAULT 0.00,
    tier_changed_at DATETIME NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tier (current_tier),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User loyalty tier tracking';

-- ============================================
-- 4. GAMIFICATION BADGES
-- ============================================
CREATE TABLE IF NOT EXISTS user_badges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    badge_key VARCHAR(50) NOT NULL COMMENT 'e.g. first_transfer, bill_master',
    badge_name VARCHAR(100) NOT NULL,
    badge_icon VARCHAR(50) DEFAULT '🏆',
    badge_description VARCHAR(255),
    progress_current INT DEFAULT 1,
    progress_target INT DEFAULT 1,
    is_earned TINYINT(1) DEFAULT 0,
    earned_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_user_badge (user_id, badge_key),
    INDEX idx_earned (is_earned),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Gamification badges and achievements';

-- ============================================
-- 5. MERCHANT OFFERS
-- ============================================
CREATE TABLE IF NOT EXISTS merchant_offers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    merchant_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    discount_percent DECIMAL(5,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    min_transaction DECIMAL(10,2) DEFAULT 0.00,
    max_discount DECIMAL(10,2) DEFAULT 0.00,
    code VARCHAR(20) NULL COMMENT 'Promo code if applicable',
    valid_from DATETIME NOT NULL,
    valid_until DATETIME NOT NULL,
    usage_limit INT DEFAULT NULL,
    usage_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_merchant (merchant_id),
    INDEX idx_valid (valid_from, valid_until),
    INDEX idx_active (is_active),
    FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Merchant promotional offers';

-- ============================================
-- 6. LOGIN DEVICES (Security)
-- ============================================
CREATE TABLE IF NOT EXISTS login_devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    device_name VARCHAR(100) DEFAULT 'Unknown Device',
    device_type ENUM('mobile', 'tablet', 'desktop', 'other') DEFAULT 'other',
    browser VARCHAR(50),
    os VARCHAR(50),
    ip_address VARCHAR(45),
    fingerprint_hash VARCHAR(64) NULL COMMENT 'Device fingerprint for tracking',
    is_trusted TINYINT(1) DEFAULT 0,
    is_current TINYINT(1) DEFAULT 0,
    last_login DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_current (user_id, is_current),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Device tracking for security';

-- ============================================
-- 7. NOTIFICATION QUEUE
-- ============================================
CREATE TABLE IF NOT EXISTS notification_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('transaction', 'cashback', 'security', 'offer', 'streak', 'system') NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(255) NULL,
    image_url VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_type (type),
    INDEX idx_sent (sent_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Push + in-app notification queue';

-- ============================================
-- 8. BILL PROVIDER CONFIG
-- ============================================
CREATE TABLE IF NOT EXISTS bill_providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    type ENUM('electricity', 'water', 'internet', 'mobile', 'tv', 'education', 'insurance', 'other') NOT NULL,
    logo_url VARCHAR(255) NULL,
    api_endpoint VARCHAR(255) NULL COMMENT 'Bill enquiry API',
    api_key_encrypted TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    processing_fee DECIMAL(5,2) DEFAULT 0.00,
    cashback_percent DECIMAL(5,2) DEFAULT 0.00,
    fields_config JSON NULL COMMENT 'Required fields: [{name, label, type}]',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bill payment providers config';

-- Insert default bill providers
INSERT INTO bill_providers (name, display_name, type, logo_url, cashback_percent, fields_config) VALUES
('nea', 'Nepal Electricity Authority', 'electricity', '/assets/images/providers/nea.png', 0.50, '[{"name":"consumer_id","label":"Consumer ID","type":"text"}]'),
('khanepani', 'Kathmandu Upatyaka Khanepani', 'water', '/assets/images/providers/khanepani.png', 0.50, '[{"name":"customer_id","label":"Customer ID","type":"text"}]'),
('worldlink', 'WorldLink Communications', 'internet', '/assets/images/providers/worldlink.png', 1.00, '[{"name":"username","label":"Username","type":"text"}]'),
('vianet', 'Vianet Communications', 'internet', '/assets/images/providers/vianet.png', 0.75, '[{"name":"user_id","label":"User ID","type":"text"}]'),
('ntc', 'Nepal Telecom', 'mobile', '/assets/images/providers/ntc.png', 1.50, '[{"name":"phone","label":"Mobile Number","type":"tel"}]'),
('ncell', 'Ncell Axiata', 'mobile', '/assets/images/providers/ncell.png', 2.00, '[{"name":"phone","label":"Mobile Number","type":"tel"}]'),
('dishhome', 'DishHome', 'tv', '/assets/images/providers/dishhome.png', 1.00, '[{"name":"vc_number","label":"VC Number","type":"text"}]'),
('dctv', 'Dish TV', 'tv', '/assets/images/providers/dctv.png', 0.75, '[{"name":"card_number","label":"Card Number","type":"text"}]')
ON DUPLICATE KEY UPDATE name=name;

-- ============================================
-- 9. USER STREAKS
-- ============================================
CREATE TABLE IF NOT EXISTS user_streaks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    current_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    last_activity_date DATE NULL,
    streak_reward_earned TINYINT(1) DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Daily login streak tracking';

-- ============================================
-- 10. KYC DOCUMENTS
-- ============================================
CREATE TABLE IF NOT EXISTS kyc_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    document_type ENUM('citizenship', 'passport', 'driving_license', 'voter_id') NOT NULL,
    document_number VARCHAR(50) NOT NULL,
    front_image VARCHAR(255) NOT NULL,
    back_image VARCHAR(255) NULL,
    selfie_image VARCHAR(255) NOT NULL,
    verified_by INT NULL,
    verified_at DATETIME NULL,
    status ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='KYC document submissions';

-- ============================================
-- INDEX OPTIMIZATIONS FOR PERFORMANCE
-- ============================================
ALTER TABLE transactions ADD INDEX IF NOT EXISTS idx_sender_date (sender_id, created_at DESC);
ALTER TABLE transactions ADD INDEX IF NOT EXISTS idx_receiver_date (receiver_id, created_at DESC);
ALTER TABLE transactions ADD INDEX IF NOT EXISTS idx_type_date (type, created_at DESC);
ALTER TABLE transactions ADD INDEX IF NOT EXISTS idx_status_date (status, created_at DESC);
ALTER TABLE balance_adjustments ADD INDEX IF NOT EXISTS idx_user_created (user_id, created_at DESC);
ALTER TABLE notifications ADD INDEX IF NOT EXISTS idx_user_read (user_id, is_read, created_at DESC);
ALTER TABLE security_logs ADD INDEX IF NOT EXISTS idx_ip_created (ip_address, created_at DESC);

-- ============================================
-- TRIGGERS FOR AUTO-MAINTENANCE
-- ============================================

DELIMITER //

-- Auto-update loyalty tier on transaction completion
CREATE TRIGGER IF NOT EXISTS trg_update_loyalty_after_txn
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    DECLARE v_count INT;
    DECLARE v_volume DECIMAL(15,2);
    
    IF NEW.status = 'completed' THEN
        -- Get monthly stats
        SELECT COUNT(*), COALESCE(SUM(amount), 0)
        INTO v_count, v_volume
        FROM transactions
        WHERE (sender_id = NEW.sender_id OR receiver_id = NEW.receiver_id)
        AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
        AND status = 'completed';
        
        -- Insert or update loyalty tier
        INSERT INTO loyalty_tiers (user_id, total_transactions, monthly_volume, current_tier)
        VALUES (
            COALESCE(NEW.sender_id, NEW.receiver_id),
            v_count,
            v_volume,
            CASE
                WHEN v_count >= 50 AND v_volume >= 100000 THEN 'platinum'
                WHEN v_count >= 20 AND v_volume >= 25000 THEN 'gold'
                WHEN v_count >= 5 AND v_volume >= 5000 THEN 'silver'
                ELSE 'bronze'
            END
        )
        ON DUPLICATE KEY UPDATE
            total_transactions = v_count,
            monthly_volume = v_volume,
            current_tier = CASE
                WHEN v_count >= 50 AND v_volume >= 100000 THEN 'platinum'
                WHEN v_count >= 20 AND v_volume >= 25000 THEN 'gold'
                WHEN v_count >= 5 AND v_volume >= 5000 THEN 'silver'
                ELSE 'bronze'
            END,
            tier_changed_at = IF(
                current_tier != CASE
                    WHEN v_count >= 50 AND v_volume >= 100000 THEN 'platinum'
                    WHEN v_count >= 20 AND v_volume >= 25000 THEN 'gold'
                    WHEN v_count >= 5 AND v_volume >= 5000 THEN 'silver'
                    ELSE 'bronze'
                END,
                NOW(),
                tier_changed_at
            ),
            updated_at = NOW();
    END IF;
END//

-- Auto-credit cashback on bill payment
CREATE TRIGGER IF NOT EXISTS trg_cashback_bill_payment
AFTER INSERT ON bill_payments
FOR EACH ROW
BEGIN
    DECLARE v_cashback DECIMAL(10,2);
    DECLARE v_provider_cashback DECIMAL(5,2);
    
    -- Get provider cashback rate
    SELECT COALESCE(cashback_percent, 0) INTO v_provider_cashback
    FROM bill_providers
    WHERE name = NEW.bill_type AND is_active = 1;
    
    SET v_cashback = ROUND(NEW.amount * v_provider_cashback / 100, 2);
    
    IF v_cashback > 0 THEN
        INSERT INTO cashback_transactions 
        (user_id, original_transaction_id, amount, type, status, description)
        VALUES 
        (NEW.user_id, CONCAT('BILL', NEW.id), v_cashback, 'bill_payment', 'pending', 
         CONCAT('Cashback on ', NEW.bill_type, ' bill payment'));
    END IF;
END//

DELIMITER ;

-- ============================================
-- SEED DEFAULT BADGES
-- ============================================
INSERT INTO user_badges (user_id, badge_key, badge_name, badge_icon, badge_description, progress_target, is_earned)
SELECT id, 'welcome', 'Welcome Aboard', '👋', 'Joined NepalPay', 1, 1
FROM users
ON DUPLICATE KEY UPDATE badge_name=badge_name;
