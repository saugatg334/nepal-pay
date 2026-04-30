<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Config;
use NepalPay\Core\Logger;
use \Database;
use \Wallet;
use \Transaction;
use \User;
use \Exception;
use \NepalPay\Services\TransactionPinService;

/**
 * Wallet Service
 * 
 * WHY: Centralizes all wallet operations (send, receive, add money, transactions).
 * Ensures:
 * - Atomic transactions (BEGIN/COMMIT/ROLLBACK)
 * - Proper audit logging
 * - Consistent balance checks
 * - Notification triggers
 * - Validation of all operations
 * 
 * This service is used by both web controllers (WalletController)
 * and API controllers (ApiWalletController).
 */
class WalletService
{
    /**
     * @var float Maximum single transaction amount (NPR)
     */
    private const MAX_SINGLE_TRANSACTION = 100000.00;
    
    /**
     * @var float Minimum transaction amount (NPR)
     */
    private const MIN_TRANSACTION = 1.00;

    /**
     * Send money from one user to another
     * 
     * IDEMPOTENCY: If $pinData['idempotency_key'] is provided, duplicate
     * requests with the same key will return the cached result without
     * executing the transfer again (prevents double-spending on retries).
     * 
     * ROW LOCKING: Uses SELECT FOR UPDATE to prevent race conditions
     * on concurrent transfers from the same wallet.
     * 
     * @throws Exception on failure
     */
    public static function sendMoney(int $fromUserId, string $recipientWalletNumber, float $amount, string $description = '', ?array $pinData = null): array
    {
        // ─── VALIDATION HARDENING ───
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        if ($amount < self::MIN_TRANSACTION) {
            throw new \InvalidArgumentException(
                sprintf('Minimum transaction amount is NPR %.2f', self::MIN_TRANSACTION)
            );
        }
        
        if ($amount > self::MAX_SINGLE_TRANSACTION) {
            throw new \InvalidArgumentException(
                sprintf('Maximum single transaction is NPR %.2f', self::MAX_SINGLE_TRANSACTION)
            );
        }
        
        // ─── IDEMPOTENCY CHECK ───
        $idempotencyKey = $pinData['idempotency_key'] ?? null;
        $endpoint = 'wallet.sendMoney';
        $requestBody = json_encode([
            'from' => $fromUserId,
            'to_wallet' => $recipientWalletNumber,
            'amount' => $amount,
            'description' => $description
        ]);
        
        if ($idempotencyKey && class_exists('NepalPay\\Services\\IdempotencyService')) {
            if (!IdempotencyService::validateKey($idempotencyKey)) {
                throw new \InvalidArgumentException('Invalid idempotency key format');
            }
            
            $cached = IdempotencyService::checkCache($fromUserId, $idempotencyKey, $endpoint, $requestBody);
            if ($cached) {
                if ($cached['status'] === 'completed') {
                    Logger::info('Idempotency cache hit - returning cached result', [
                        'user_id' => $fromUserId,
                        'key' => $idempotencyKey
                    ]);
                    return json_decode($cached['response_body'], true) ?? [];
                }
                if ($cached['status'] === 'pending') {
                    throw new \Exception('Transaction is already being processed. Please wait.');
                }
                if ($cached['status'] === 'failed') {
                    throw new \Exception('Previous attempt failed: ' . ($cached['error_message'] ?? 'Unknown error'));
                }
            }
            
            // Mark as pending before proceeding
            IdempotencyService::markPending($fromUserId, $idempotencyKey, $endpoint, $requestBody);
        }
        
        // ─── PIN VERIFICATION ───
        if (TransactionPinService::isRequired($fromUserId)) {
            if (!$pinData || !isset($pinData['pin'])) {
                if ($idempotencyKey) {
                    IdempotencyService::cacheError($fromUserId, $idempotencyKey, $endpoint, $requestBody, 400, 'Transaction PIN required');
                }
                throw new \Exception('Transaction PIN required');
            }
            $pin = $pinData['pin'];
            if (!TransactionPinService::verify($fromUserId, $pin)) {
                $remaining = TransactionPinService::remainingAttempts($fromUserId);
                $errorMsg = 'Invalid PIN. ' . $remaining . ' attempts remaining.';
                if ($idempotencyKey) {
                    IdempotencyService::cacheError($fromUserId, $idempotencyKey, $endpoint, $requestBody, 403, $errorMsg);
                }
                throw new \Exception($errorMsg);
            }
        }
        
        // ─── RECIPIENT VALIDATION ───
        $recipientWallet = Wallet::findByNumber($recipientWalletNumber);
        if (!$recipientWallet) {
            if ($idempotencyKey) {
                IdempotencyService::cacheError($fromUserId, $idempotencyKey, $endpoint, $requestBody, 404, 'Recipient wallet not found');
            }
            throw new \Exception('Recipient wallet not found');
        }
        $recipientUserId = $recipientWallet['user_id'];
        
        // Can't send to self
        if ($recipientUserId == $fromUserId) {
            if ($idempotencyKey) {
                IdempotencyService::cacheError($fromUserId, $idempotencyKey, $endpoint, $requestBody, 400, 'Cannot send money to yourself');
            }
            throw new \Exception('Cannot send money to yourself');
        }
        
        // ─── FRAUD DETECTION ───
        if (class_exists('NepalPay\\Services\\FraudDetectionService')) {
            $fraudResult = FraudDetectionService::assess(
                $fromUserId,
                $amount,
                'user',
                $recipientUserId,
                ['ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown']
            );
            
            // Block if critical risk
            if ($fraudResult['should_block']) {
                // ALERT: Trigger real fraud alert instead of just logging
                if (class_exists('NepalPay\\Services\\AlertService')) {
                    AlertService::triggerFraudAlert(
                        $fromUserId,
                        'PREVENTED',
                        implode(', ', $fraudResult['reasons'] ?? ['Unknown']),
                        $fraudResult['risk_level'],
                        $amount
                    );
                }
                
                Logger::security('Transaction blocked by fraud detection', [
                    'user_id' => $fromUserId,
                    'amount' => $amount,
                    'risk_score' => $fraudResult['risk_score'],
                    'risk_level' => $fraudResult['risk_level'],
                    'reasons' => $fraudResult['reasons']
                ]);
                
                if ($idempotencyKey) {
                    IdempotencyService::cacheError($fromUserId, $idempotencyKey, $endpoint, $requestBody, 403, 'Transaction blocked due to security concerns');
                }
                
                throw new \Exception('Transaction blocked due to security concerns. Please contact support.');
            }
            
            // Require additional verification for high risk
            if ($fraudResult['requires_verification'] && !isset($pinData['verified']) && !TransactionPinService::isVerifiedRecently($fromUserId)) {
                Logger::warning('Transaction requires additional verification', [
                    'user_id' => $fromUserId,
                    'risk_level' => $fraudResult['risk_level'],
                    'risk_score' => $fraudResult['risk_score']
                ]);
                throw new \Exception('Additional verification required. Re-enter your transaction PIN.');
            }
        }
        
        // ─── TRANSACTION LIMITS ───
        if (class_exists('NepalPay\\Services\\FraudDetectionService')) {
            $limitCheck = FraudDetectionService::checkDailyLimit($fromUserId, $amount);
            if (!$limitCheck['allowed']) {
                if ($idempotencyKey) {
                    IdempotencyService::cacheError($fromUserId, $idempotencyKey, $endpoint, $requestBody, 429, $limitCheck['reason']);
                }
                throw new \Exception($limitCheck['reason']);
            }
        }
        
        // ─── ATOMIC TRANSFER WITH ROW LOCKING ───
        Database::beginTransaction();
        
        try {
            // Lock sender wallet row to prevent race conditions
            $senderWallet = Database::fetch(
                "SELECT * FROM wallets WHERE user_id = ? FOR UPDATE",
                [$fromUserId]
            );
            
            if (!$senderWallet) {
                throw new Exception('Sender wallet not found');
            }
            
            // Re-check balance after acquiring lock
            if ($senderWallet['balance'] < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            // Lock recipient wallet row
            $recipientWalletLocked = Database::fetch(
                "SELECT * FROM wallets WHERE id = ? FOR UPDATE",
                [$recipientWallet['id']]
            );
            
            if (!$recipientWalletLocked) {
                throw new Exception('Recipient wallet not found');
            }
            
            // Calculate fees
            $fee = self::calculateTransferFee($amount);
            $totalDebit = $amount + $fee;
            
            if ($senderWallet['balance'] < $totalDebit) {
                throw new Exception('Insufficient balance including fees');
            }
            
            // Debit sender
            $newSenderBalance = $senderWallet['balance'] - $totalDebit;
            Database::query(
                "UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?",
                [$newSenderBalance, $senderWallet['id']]
            );
            
            // Credit recipient
            $newRecipientBalance = $recipientWalletLocked['balance'] + $amount;
            Database::query(
                "UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?",
                [$newRecipientBalance, $recipientWalletLocked['id']]
            );
            
            // Record transaction
            $transactionId = self::generateTransactionId();
            Database::query(
                "INSERT INTO transactions 
                 (transaction_id, sender_id, receiver_id, amount, fee, type, status, description, created_at)
                 VALUES (?, ?, ?, ?, ?, 'send', 'completed', ?, NOW())",
                [
                    $transactionId,
                    $fromUserId,
                    $recipientUserId,
                    $amount,
                    $fee,
                    $description
                ]
            );
            
            // Record balance adjustments for audit
            self::logBalanceAdjustment(
                $fromUserId,
                $senderWallet['id'],
                'debit',
                $amount,
                $senderWallet['balance'],
                $newSenderBalance,
                'money_sent',
                $transactionId,
                $fromUserId
            );
            
            self::logBalanceAdjustment(
                $recipientUserId,
                $recipientWalletLocked['id'],
                'credit',
                $amount,
                $recipientWalletLocked['balance'],
                $newRecipientBalance,
                'money_received',
                $transactionId,
                $fromUserId
            );
            
            Database::commit();
            
            // Build success response
            $response = [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'fee' => $fee,
                'new_balance' => $newSenderBalance,
                'recipient_wallet' => $recipientWalletNumber,
                'timestamp' => time()
            ];
            
            // Cache idempotency response
            if ($idempotencyKey) {
                IdempotencyService::cacheSuccess(
                    $fromUserId,
                    $idempotencyKey,
                    $endpoint,
                    $requestBody,
                    200,
                    json_encode($response)
                );
            }
            
            // Send notification (fire and forget)
            self::sendTransferNotification($fromUserId, $recipientUserId, $amount, $transactionId);
            
            Logger::info('Money transferred', [
                'transaction_id' => $transactionId,
                'from' => $fromUserId,
                'to' => $recipientUserId,
                'amount' => $amount
            ]);
            
            return $response;
            
        } catch (Exception $e) {
            Database::rollBack();
            
            // Cache failure for idempotency
            if ($idempotencyKey) {
                IdempotencyService::cacheError(
                    $fromUserId,
                    $idempotencyKey,
                    $endpoint,
                    $requestBody,
                    500,
                    $e->getMessage()
                );
            }
            
            Logger::error('Transfer failed', [
                'from' => $fromUserId,
                'to' => $recipientWalletNumber,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Add money to wallet (top-up)
     * 
     * IDEMPOTENCY: Supports idempotency via $pinData['idempotency_key']
     * to prevent duplicate top-ups on network retries.
     * 
     * ROW LOCKING: Uses SELECT FOR UPDATE on wallet row.
     */
    public static function addMoney(int $userId, float $amount, string $source, string $reference, ?array $pinData = null): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        if ($amount > self::MAX_SINGLE_TRANSACTION) {
            throw new \InvalidArgumentException(
                sprintf('Maximum top-up amount is NPR %.2f', self::MAX_SINGLE_TRANSACTION)
            );
        }
        
        // ─── IDEMPOTENCY CHECK ───
        $idempotencyKey = $pinData['idempotency_key'] ?? null;
        $endpoint = 'wallet.addMoney';
        $requestBody = json_encode([
            'user_id' => $userId,
            'amount' => $amount,
            'source' => $source,
            'reference' => $reference
        ]);
        
        if ($idempotencyKey && class_exists('NepalPay\\Services\\IdempotencyService')) {
            if (!IdempotencyService::validateKey($idempotencyKey)) {
                throw new \InvalidArgumentException('Invalid idempotency key format');
            }
            
            $cached = IdempotencyService::checkCache($userId, $idempotencyKey, $endpoint, $requestBody);
            if ($cached) {
                if ($cached['status'] === 'completed') {
                    return json_decode($cached['response_body'], true) ?? [];
                }
                if ($cached['status'] === 'pending') {
                    throw new \Exception('Top-up is already being processed. Please wait.');
                }
                if ($cached['status'] === 'failed') {
                    throw new \Exception('Previous attempt failed: ' . ($cached['error_message'] ?? 'Unknown error'));
                }
            }
            
            IdempotencyService::markPending($userId, $idempotencyKey, $endpoint, $requestBody);
        }
        
        // ─── ATOMIC TOP-UP WITH ROW LOCKING ───
        Database::beginTransaction();
        
        try {
            // Lock wallet row
            $wallet = Database::fetch(
                "SELECT * FROM wallets WHERE user_id = ? FOR UPDATE",
                [$userId]
            );
            
            if (!$wallet) {
                throw new Exception('Wallet not found');
            }
            
            $oldBalance = $wallet['balance'];
            $newBalance = $oldBalance + $amount;
            
            Database::query(
                "UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?",
                [$newBalance, $wallet['id']]
            );
            
            $transactionId = self::generateTransactionId();
            Database::query(
                "INSERT INTO transactions 
                 (transaction_id, receiver_id, amount, type, status, description, reference_id, created_at)
                 VALUES (?, ?, ?, 'add_money', 'completed', ?, ?, NOW())",
                [
                    $transactionId,
                    $userId,
                    $amount,
                    "Top-up via {$source}",
                    $reference
                ]
            );
            
            self::logBalanceAdjustment(
                $userId,
                $wallet['id'],
                'credit',
                $amount,
                $oldBalance,
                $newBalance,
                'wallet_topup',
                $transactionId,
                null
            );
            
            Database::commit();
            
            $response = [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'source' => $source,
                'timestamp' => time()
            ];
            
            // Cache idempotency response
            if ($idempotencyKey) {
                IdempotencyService::cacheSuccess(
                    $userId,
                    $idempotencyKey,
                    $endpoint,
                    $requestBody,
                    200,
                    json_encode($response)
                );
            }
            
            Logger::info('Wallet top-up', [
                'user_id' => $userId,
                'amount' => $amount,
                'transaction_id' => $transactionId
            ]);
            
            return $response;
            
        } catch (Exception $e) {
            Database::rollBack();
            
            if ($idempotencyKey) {
                IdempotencyService::cacheError(
                    $userId,
                    $idempotencyKey,
                    $endpoint,
                    $requestBody,
                    500,
                    $e->getMessage()
                );
            }
            
            Logger::error('Top-up failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Get user's transactions with filters
     */
    public static function getTransactions(int $userId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        $where = ["(sender_id = ? OR receiver_id = ?)"];
        $params = [$userId, $userId];
        
        if (!empty($filters['type'])) {
            $where[] = "type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['from_date'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['from_date'];
        }
        
        if (!empty($filters['to_date'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['to_date'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT * FROM transactions 
                WHERE {$whereClause} 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $transactions = Database::fetchAll($sql, $params);
        
        // Count total
        $countSql = "SELECT COUNT(*) as total FROM transactions WHERE {$whereClause}";
        $countResult = Database::fetch($countSql, array_slice($params, 0, -2));
        $total = $countResult['total'] ?? 0;
        
        return [
            'transactions' => $transactions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }
    
    /**
     * Get wallet balance by user ID
     */
    public static function getBalance(int $userId): float
    {
        $wallet = Wallet::getByUserId($userId);
        return $wallet ? (float) $wallet['balance'] : 0.0;
    }
    
    /**
     * Calculate transfer fee (if any)
     */
    private static function calculateTransferFee(float $amount): float
    {
        // Future: dynamic fees based on amount, user type, etc.
        // For now: free transfers
        return 0.0;
    }
    
    /**
     * Generate unique transaction ID
     */
    private static function generateTransactionId(): string
    {
        return 'TXN' . time() . random_int(1000, 9999);
    }
    
    /**
     * Log balance adjustment for audit
     */
    private static function logBalanceAdjustment(
        int $userId,
        int $walletId,
        string $type,
        float $amount,
        float $before,
        float $after,
        string $reason,
        string $referenceId,
        ?int $adjustedBy
    ): void {
        try {
            $sql = "INSERT INTO balance_adjustments 
                    (user_id, wallet_id, adjustment_type, amount, balance_before, balance_after, reason, reference_id, adjusted_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            Database::query($sql, [
                $userId,
                $walletId,
                $type,
                $amount,
                $before,
                $after,
                $reason,
                $referenceId,
                $adjustedBy
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to log balance adjustment', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Send transfer notifications (fire and forget)
     */
    private static function sendTransferNotification(int $fromUserId, int $toUserId, float $amount, string $txnId): void
    {
        try {
            // TODO: NotificationService::sendMoneySent($fromUserId, $toUserId, $amount, $txnId);
            // For now just log
            Logger::info('Transfer notification', [
                'from' => $fromUserId,
                'to' => $toUserId,
                'amount' => $amount,
                'txn' => $txnId
            ]);
        } catch (\Exception $e) {
            // Non-blocking
        }
    }
}
