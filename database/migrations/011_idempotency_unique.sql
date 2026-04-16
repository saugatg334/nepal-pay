-- Fintech Critical: Unique Idempotency + Status ENUM
ALTER TABLE transactions 
ADD UNIQUE KEY uk_idempotency (idempotency_key),
ADD COLUMN status ENUM('pending', 'processing', 'completed', 'failed', 'reversed') DEFAULT 'pending' AFTER type,
ADD INDEX idx_status_created (status, created_at);
SELECT 'Idempotency unique + status enforced' as result;

