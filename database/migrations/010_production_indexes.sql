-- Production Indexes for Performance + Fintech Scale
ALTER TABLE transactions ADD INDEX idx_status_created (status, created_at);
ALTER TABLE ledger_entries ADD INDEX idx_user_created (user_id, created_at);
ALTER TABLE audit_logs ADD INDEX idx_user_action (user_id, action);
ALTER TABLE rate_limit_events ADD INDEX idx_identifier_created (identifier, created_at);
ALTER TABLE users ADD INDEX idx_phone_status (phone, status);
SELECT 'Indexes added for production scale' as result;

