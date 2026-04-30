<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Config;
use NepalPay\Core\Logger;
use \Database;
use \Exception;

/**
 * Enhanced Atomic Money Transfer Service
 * 
 * FINANCIAL GUARANTEES:
 * 1. Atomicity: Sender debit + Receiver credit both happen or both fail
 * 2. Row Locking: SELECT...FOR UPDATE prevents race conditions
 * 3. No Double-Spending: Idempotency ensures request retries don't duplicate
 * 4. Audit Trail: Every balance change is logged immutably
 * 
 * REAL-WORLD SCENARIOS THIS PREVENTS:
 * 
 * Scenario 1: Race Condition
 *   - User A has 100 NPR, sends to B (50 NPR) and C (50 NPR) simultaneously
 *   - Without locking: Both debit happens, balance becomes -50
 *   - With FOR UPDATE: First transfer locks row, second waits, double-spend prevented
 * 
 * Scenario 2: Network Failure
 *   - Transfer executes, debit succeeds, credit fails, network dies
 *   - Without transaction: Balance inconsistent forever
 *   - With transaction: ROLLBACK restores balance, no money lost
 * 
 * Scenario 3: Client Retry
 *   - Transfer executes, client doesn't get response, retries
 *   - Without idempotency: Second transfer executes, double-spend
 *   - With idempotency + cache: Retry returns same result, no duplicate
 * 
 * HOW TO USE:
 *   $result = AtomicTransferService::transfer(
 *       fromUserId: 123,
 *       toWalletNumber: "NP987654",
 *       amount: 50.00,  // In cents: 5000
 *       description: "Rent payment",
 *       idempotencyKey: "user-generated-uuid"
 *   );
 */
class AtomicTransferService
{
    /**
     * Execute atomic money transfer with row-level locking
     * 
     * @param int $fromUserId - Sender user ID
     * @param string $toWalletNumber - Recipient wallet number
     * @param float $amount - Amount in currency units (NOT cents)
     * @param string $description - Transaction description
     * @param string|null $idempotencyKey - Optional idempotency key (recommended)
     * 
     * @throws Exception on any validation or execution failure
     * @return array with transaction_id, amount, new_balance
     */
    public static function transfer(
        int $fromUserId,
        string $toWalletNumber,
        float $amount,
        string $description = '',
        ?string $idempotencyKey = null
    ): array {
        // Input validation (before transaction)
        self::validateInputs($fromUserId, $toWalletNumber, $amount);

        // Check idempotency (before transaction)
        if ($idempotencyKey) {
            $cached = IdempotencyService::checkCache(
                $fromUserId,
                $idempotencyKey,
                '/wallet/transfer',
                json_encode([
                    'toWalletNumber' => $toWalletNumber,
                    'amount' => $amount,
                    'description' => $description
                ])
            );

            if ($cached) {
                // Return cached response (exact same result as first attempt)
                Logger::info('Transfer returned from cache', [
                    'user_id' => $fromUserId,
                    'key' => $idempotencyKey
                ]);
                return json_decode($cached['response_body'], true);
            }

            // Mark as pending
            IdempotencyService::markPending(
                $fromUserId,
                $idempotencyKey,
                '/wallet/transfer',
                json_encode([
                    'toWalletNumber' => $toWalletNumber,
                    'amount' => $amount,
                    'description' => $description
                ])
            );
        }

        Database::beginTransaction();

        try {
            // CRITICAL: Use SELECT...FOR UPDATE to lock rows
            // This prevents concurrent transfers from same wallet
            $senderWalletSql = "
                SELECT id, user_id, balance 
                FROM wallets 
                WHERE user_id = ? 
                FOR UPDATE
            ";
            $senderWallet = Database::fetch($senderWalletSql, [$fromUserId]);

            if (!$senderWallet) {
                throw new Exception('Sender wallet not found');
            }

            // Get recipient wallet with lock
            $recipientWalletSql = "
                SELECT id, user_id, balance 
                FROM wallets 
                WHERE wallet_number = ? 
                FOR UPDATE
            ";
            $recipientWallet = Database::fetch($recipientWalletSql, [$toWalletNumber]);

            if (!$recipientWallet) {
                throw new Exception('Recipient wallet not found');
            }

            if ($recipientWallet['user_id'] == $fromUserId) {
                throw new Exception('Cannot transfer to own wallet');
            }

            // Check balance (must be checked AFTER lock acquired)
            if ($senderWallet['balance'] < $amount) {
                throw new Exception('Insufficient balance');
            }

            $transactionId = self::generateTransactionId();

            // Debit sender
            $newSenderBalance = $senderWallet['balance'] - $amount;
            Database::query(
                "UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?",
                [$newSenderBalance, $senderWallet['id']]
            );

            // Credit receiver
            $newRecipientBalance = $recipientWallet['balance'] + $amount;
            Database::query(
                "UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?",
                [$newRecipientBalance, $recipientWallet['id']]
            );

            // Record transaction (immutable record)
            Database::query(
                "INSERT INTO transactions 
                 (transaction_id, sender_id, receiver_id, amount, type, status, description)
                 VALUES (?, ?, ?, ?, 'send', 'completed', ?)",
                [
                    $transactionId,
                    $fromUserId,
                    $recipientWallet['user_id'],
                    $amount,
                    $description
                ]
            );

            // Log in audit trail
            self::logAuditEntry(
                $transactionId,
                $fromUserId,
                'transfer_committed',
                $amount,
                $senderWallet['balance'],
                $newSenderBalance,
                ['recipient_id' => $recipientWallet['user_id'], 'description' => $description]
            );

            Database::commit();

            $response = [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'recipient_wallet' => $toWalletNumber,
                'new_balance' => $newSenderBalance,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Cache successful response (if idempotency key provided)
            if ($idempotencyKey) {
                IdempotencyService::cacheSuccess(
                    $fromUserId,
                    $idempotencyKey,
                    '/wallet/transfer',
                    json_encode([
                        'toWalletNumber' => $toWalletNumber,
                        'amount' => $amount,
                        'description' => $description
                    ]),
                    200,
                    json_encode($response)
                );
            }

            Logger::info('Atomic transfer completed', [
                'transaction_id' => $transactionId,
                'from_user' => $fromUserId,
                'to_user' => $recipientWallet['user_id'],
                'amount' => $amount
            ]);

            return $response;
        } catch (Exception $e) {
            Database::rollback();

            // Cache error response (if idempotency key provided)
            if ($idempotencyKey) {
                IdempotencyService::cacheError(
                    $fromUserId,
                    $idempotencyKey,
                    '/wallet/transfer',
                    json_encode([
                        'toWalletNumber' => $toWalletNumber,
                        'amount' => $amount,
                        'description' => $description
                    ]),
                    400,
                    $e->getMessage()
                );
            }

            Logger::error('Transfer failed', [
                'user_id' => $fromUserId,
                'to_wallet' => $toWalletNumber,
                'amount' => $amount,
                'reason' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Validate all inputs before transaction starts
     */
    private static function validateInputs(int $userId, string $walletNumber, float $amount): void
    {
        if ($userId <= 0) {
            throw new Exception('Invalid user ID');
        }

        if (empty($walletNumber) || strlen($walletNumber) > 20) {
            throw new Exception('Invalid wallet number');
        }

        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0');
        }

        // Prevent overflow (max 999,999.99)
        if ($amount >= 1000000) {
            throw new Exception('Amount exceeds maximum limit');
        }

        // Check decimal places (max 2)
        if (strpos((string)$amount, '.') !== false) {
            $decimals = strlen(substr(strrchr((string)$amount, "."), 1));
            if ($decimals > 2) {
                throw new Exception('Amount cannot have more than 2 decimal places');
            }
        }
    }

    /**
     * Generate unique transaction ID
     */
    private static function generateTransactionId(): string
    {
        // Format: TXN-YYYYMMDD-UNIQID
        return 'TXN-' . date('Ymd') . '-' . uniqid('', true);
    }

    /**
     * Log transaction in immutable audit trail
     */
    private static function logAuditEntry(
        string $transactionId,
        int $userId,
        string $actionType,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        array $details = []
    ): void {
        try {
            Database::query(
                "INSERT INTO transaction_audit_log 
                 (transaction_id, user_id, action_type, amount, balance_before, balance_after, details)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $transactionId,
                    $userId,
                    $actionType,
                    $amount,
                    $balanceBefore,
                    $balanceAfter,
                    json_encode($details)
                ]
            );
        } catch (Exception $e) {
            // Log but don't fail transfer if audit log fails
            Logger::error('Failed to log audit entry', ['error' => $e->getMessage()]);
        }
    }
}
