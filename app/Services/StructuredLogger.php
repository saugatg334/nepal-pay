<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Logger;
use \Exception;

/**
 * Structured Transaction Logger
 * 
 * REQUIREMENT: All financial transactions must be logged in structured JSON format
 *              for compliance, auditing, and incident investigation.
 * 
 * WHAT TO LOG:
 * - user_id: Who performed the action
 * - transaction_id: Unique transaction identifier
 * - action: What happened (transfer, add_money, withdraw, etc)
 * - amount: How much money moved
 * - recipient_id: Who received the money (if applicable)
 * - balance_before: Wallet state before transaction
 * - balance_after: Wallet state after transaction
 * - ip: Client IP address (for fraud tracking)
 * - user_agent: Client device/browser (for device verification)
 * - timestamp: When it happened (UTC)
 * - status: success, failed, pending
 * - error_reason: Why it failed (if applicable)
 * 
 * WHY STRUCTURED (JSON):
 * - Machine-readable: Can be indexed by ELK, Splunk, Datadog
 * - Queryable: Easy to find all transfers > 10,000, all IPs, etc
 * - Parseable: Automated fraud detection can analyze patterns
 * - Compliant: Required for PCI-DSS, PSD2, GDPR audits
 * 
 * USAGE:
 *   StructuredLogger::logTransaction([
 *       'user_id' => 123,
 *       'transaction_id' => 'TXN-20240425-abc123',
 *       'action' => 'transfer',
 *       'amount' => 5000.00,
 *       'recipient_id' => 456,
 *       'balance_before' => 10000.00,
 *       'balance_after' => 4999.00,  // After transfer + fee
 *       'fee' => 1.00,
 *       'status' => 'success',
 *       'ip' => '192.168.1.1',
 *       'user_agent' => 'Mozilla/5.0...'
 *   ]);
 */
class StructuredLogger
{
    /**
     * Log a financial transaction in structured JSON format
     * 
     * @param array $data - Transaction data with keys: user_id, transaction_id, 
     *                      action, amount, etc (see class doc)
     */
    public static function logTransaction(array $data): void
    {
        $entry = self::normalize($data);
        
        // Write to structured log file (JSON format, one per line)
        $logFile = $_ENV['TRANSACTION_LOG_FILE'] ?? __DIR__ . '/../../logs/transactions.jsonl';
        
        try {
            $line = json_encode($entry) . PHP_EOL;
            
            // Append to log file
            @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
            
            // Also send to Logger (for console/syslog)
            Logger::info("Transaction: {$entry['action']}", [
                'transaction_id' => $entry['transaction_id'],
                'user_id' => $entry['user_id'],
                'amount' => $entry['amount'],
                'status' => $entry['status']
            ]);
        } catch (Exception $e) {
            // Never fail a transaction because logging failed
            Logger::error('Failed to log transaction', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log a transfer between two users
     */
    public static function logTransfer(
        string $transactionId,
        int $senderId,
        int $recipientId,
        float $amount,
        float $fee,
        float $senderBalanceBefore,
        float $senderBalanceAfter,
        string $description = ''
    ): void {
        self::logTransaction([
            'user_id' => $senderId,
            'transaction_id' => $transactionId,
            'action' => 'transfer',
            'amount' => $amount,
            'fee' => $fee,
            'total_debit' => $amount + $fee,
            'recipient_id' => $recipientId,
            'balance_before' => $senderBalanceBefore,
            'balance_after' => $senderBalanceAfter,
            'description' => $description,
            'status' => 'success',
            'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }

    /**
     * Log an add money operation (KYC deposit)
     */
    public static function logAddMoney(
        string $transactionId,
        int $userId,
        float $amount,
        string $source,
        float $balanceBefore,
        float $balanceAfter
    ): void {
        self::logTransaction([
            'user_id' => $userId,
            'transaction_id' => $transactionId,
            'action' => 'add_money',
            'amount' => $amount,
            'source' => $source,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'status' => 'success',
            'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    /**
     * Log a failed transaction
     */
    public static function logFailure(
        string $transactionId,
        int $userId,
        string $action,
        float $amount,
        string $reason
    ): void {
        self::logTransaction([
            'user_id' => $userId,
            'transaction_id' => $transactionId,
            'action' => $action,
            'amount' => $amount,
            'status' => 'failed',
            'error_reason' => $reason,
            'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    /**
     * Normalize and validate transaction data
     */
    private static function normalize(array $data): array
    {
        return [
            'timestamp' => date('Y-m-d\TH:i:s\Z'),  // ISO 8601 UTC
            'user_id' => (int)($data['user_id'] ?? 0),
            'transaction_id' => (string)($data['transaction_id'] ?? ''),
            'action' => (string)($data['action'] ?? 'unknown'),
            'amount' => (float)($data['amount'] ?? 0),
            'fee' => (float)($data['fee'] ?? 0),
            'total_debit' => (float)($data['total_debit'] ?? $data['amount'] ?? 0),
            'recipient_id' => (int)($data['recipient_id'] ?? 0),
            'balance_before' => (float)($data['balance_before'] ?? 0),
            'balance_after' => (float)($data['balance_after'] ?? 0),
            'status' => in_array($data['status'] ?? 'unknown', ['success', 'failed', 'pending'])
                ? $data['status']
                : 'unknown',
            'error_reason' => (string)($data['error_reason'] ?? ''),
            'description' => (string)($data['description'] ?? ''),
            'ip' => (string)($data['ip'] ?? 'unknown'),
            'user_agent' => substr((string)($data['user_agent'] ?? 'unknown'), 0, 255),
            'source' => (string)($data['source'] ?? 'api')
        ];
    }

    /**
     * Query transaction logs (simplified)
     * 
     * For production, use ELK/Splunk/Datadog instead
     * This is just for basic analysis
     */
    public static function getRecentTransactions(
        int $userId = 0,
        int $limit = 100
    ): array {
        $logFile = $_ENV['TRANSACTION_LOG_FILE'] ?? __DIR__ . '/../../logs/transactions.jsonl';
        
        if (!file_exists($logFile)) {
            return [];
        }

        $lines = array_slice(
            file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES),
            -$limit
        );

        $transactions = [];
        foreach ($lines as $line) {
            try {
                $entry = json_decode($line, true);
                if ($userId === 0 || $entry['user_id'] === $userId) {
                    $transactions[] = $entry;
                }
            } catch (Exception $e) {
                // Skip malformed lines
            }
        }

        return $transactions;
    }
}
