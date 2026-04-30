-- NepalPay Wallet Migration 003
-- Additional performance indexes for production scaling
-- Run after migrations 001 and 002

-- Notifications: optimize queries for user feed and unread count
-- Existing index: idx_user_notification (user_id, is_read)
-- This composite covers WHERE user_id=?, is_read=0 but not ORDER BY created_at
-- Adding covering index for sorted queries
ALTER TABLE notifications 
    ADD INDEX IF NOT EXISTS idx_user_created (user_id, created_at DESC),
    ADD INDEX IF NOT EXISTS idx_user_unread_created (user_id, is_read, created_at DESC);

-- Transactions: support queries like WHERE (sender_id = ? OR receiver_id = ?) ORDER BY created_at
-- Individual indexes ok but for frequent "my transactions" we add:
ALTER TABLE transactions 
    ADD INDEX IF NOT EXISTS idx_sender_created (sender_id, created_at DESC),
    ADD INDEX IF NOT EXISTS idx_receiver_created (receiver_id, created_at DESC);

-- Users: support search by phone/email (already indexed individually) but also by role & active
-- Composite for admin user listing with filters
ALTER TABLE users 
    ADD INDEX IF NOT EXISTS idx_role_active (role, is_active, created_at DESC);

-- Login attempts: track by IP and locked status for admin security review
ALTER TABLE login_attempts 
    ADD INDEX IF NOT EXISTS idx_locked (isLocked, locked_until);

-- Bill payments: usually query by user and date
ALTER TABLE bill_payments 
    ADD INDEX IF NOT EXISTS idx_user_created (user_id, created_at DESC);

-- Admin actions: audit log queries often filter by admin or target
ALTER TABLE admin_actions 
    ADD INDEX IF NOT EXISTS idx_admin_created (admin_id, created_at DESC),
    ADD INDEX IF NOT EXISTS idx_target_created (target_user_id, created_at DESC);

-- Balance adjustments: user + date queries
ALTER TABLE balance_adjustments 
    ADD INDEX IF NOT EXISTS idx_user_created (user_id, created_at DESC),
    ADD INDEX IF NOT EXISTS idx_reference (reference_id);

-- Sessions: cleanup expired sessions efficiently
ALTER TABLE sessions 
    ADD INDEX IF NOT EXISTS idx_expires (expires_at) 
    WHERE is_active = 1;
