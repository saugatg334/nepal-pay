<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Logger;
use \Database;
use \Exception;

/**
 * Reconciliation Service
 * 
 * PROBLEM: Database corruption, code bugs, or external interference could cause
 *          wallet balances to diverge from actual transaction history.
 *          Without detection, the discrepancy compounds until system is insolvent.
 * 
 * SOLUTION: Nightly job compares each wallet's balance against sum of all 
 *           transactions. If mismatch found, we log critical alert and flag account.
 * 
 * HOW IT WORKS:
 * 1. For each wallet, calculate expected_balance = SUM(all transactions)
 * 2. Get current balance from wallets table
 * 3. If they don't match, insert into reconciliation_logs with ALERT
 * 4. Operations team reviews and manually reconciles if needed
 * 
 * MATHEMATICAL VERIFICATION:
 * Expected Balance = Initial Balance + All Credits - All Debits
 *   WHERE transaction.type IN ('receive', 'add_money', 'refund') = CREDIT
 *   AND   transaction.type IN ('send', 'withdraw', 'fee') = DEBIT
 * 
 * RUN VIA CRON:
 * 0 2 * * * php /var/www/wallet/cron/reconcile_balances.php
 * 
 * Or add to your scheduler:
 * ReconciliationService::reconcileAllWallets()
 */
class ReconciliationService
{
    /**
     * Run full reconciliation for all active wallets
     * 
     * @return array with total_checked, mismatches found, errors
     */
    public static function reconcileAllWallets(): array
    {
        $results = [
            'total_checked' => 0,
            'mismatches_found' => 0,
            'errors' => [],
            'start_time' => date('Y-m-d H:i:s'),
            'wallet_mismatches' => []
        ];

        try {
            // Get all active wallets
            $sql = "SELECT id, user_id, balance FROM wallets WHERE is_active = 1";
            $wallets = Database::all($sql, []);

            foreach ($wallets as $wallet) {
                $results['total_checked']++;

                try {
                    $mismatch = self::reconcileWallet(
                        $wallet['id'],
                        $wallet['user_id'],
                        $wallet['balance']
                    );

                    if ($mismatch) {
                        $results['mismatches_found']++;
                        $results['wallet_mismatches'][] = $mismatch;
                    }
                } catch (Exception $e) {
                    $results['errors'][] = [
                        'wallet_id' => $wallet['id'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            $results['end_time'] = date('Y-m-d H:i:s');

            // Log summary
            Logger::info('Reconciliation completed', [
                'total_checked' => $results['total_checked'],
                'mismatches' => $results['mismatches_found'],
                'errors' => count($results['errors']),
                'start_time' => $results['start_time'],
                'end_time' => $results['end_time']
            ]);

            // Alert if mismatches found
            if ($results['mismatches_found'] > 0) {
                Logger::security('CRITICAL: Balance mismatches detected', [
                    'count' => $results['mismatches_found'],
                    'details' => $results['wallet_mismatches']
                ]);

                // In production, send alert to operations team
                // AlertService::sendCriticalAlert('Balance Reconciliation Failed', $results);
            }

            return $results;
        } catch (Exception $e) {
            Logger::error('Reconciliation failed fatally', ['error' => $e->getMessage()]);
            return array_merge($results, ['fatal_error' => $e->getMessage()]);
        }
    }

    /**
     * Reconcile a single wallet
     * 
     * @return array|null - Mismatch details if found, null if balanced
     */
    public static function reconcileWallet(
        int $walletId,
        int $userId,
        float $actualBalance
    ): ?array {
        try {
            // Calculate expected balance from transaction history
            $expectedBalance = self::calculateExpectedBalance($userId);

            // Get transaction count
            $txnCount = Database::fetch(
                "SELECT COUNT(*) as count FROM transactions WHERE sender_id = ? OR receiver_id = ?",
                [$userId, $userId]
            )['count'] ?? 0;

            // Compare
            $difference = $expectedBalance - $actualBalance;
            $isReconciled = abs($difference) < 0.01;  // Allow 1 cent rounding error

            if (!$isReconciled) {
                // Mismatch detected - log it
                Database::query(
                    "INSERT INTO reconciliation_logs 
                     (wallet_id, user_id, expected_balance, actual_balance, difference, transaction_count, is_reconciled)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $walletId,
                        $userId,
                        $expectedBalance,
                        $actualBalance,
                        $difference,
                        $txnCount,
                        0
                    ]
                );

                Logger::security('Balance mismatch detected', [
                    'wallet_id' => $walletId,
                    'user_id' => $userId,
                    'expected' => $expectedBalance,
                    'actual' => $actualBalance,
                    'difference' => $difference,
                    'transaction_count' => $txnCount
                ]);

                return [
                    'wallet_id' => $walletId,
                    'user_id' => $userId,
                    'expected_balance' => $expectedBalance,
                    'actual_balance' => $actualBalance,
                    'difference' => $difference,
                    'transaction_count' => $txnCount
                ];
            } else {
                // Balanced - log success for audit
                Database::query(
                    "INSERT INTO reconciliation_logs 
                     (wallet_id, user_id, expected_balance, actual_balance, difference, transaction_count, is_reconciled)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $walletId,
                        $userId,
                        $expectedBalance,
                        $actualBalance,
                        $difference,
                        $txnCount,
                        1
                    ]
                );

                return null;  // No mismatch
            }
        } catch (Exception $e) {
            Logger::error('Failed to reconcile wallet', [
                'wallet_id' => $walletId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate expected balance from transaction history
     * 
     * FORMULA:
     * Initial Balance + All Credits - All Debits
     * 
     * Credits (additions to wallet):
     *   - 'receive': Money sent by others
     *   - 'add_money': KYC-verified deposits
     *   - 'refund': Reversed transactions
     * 
     * Debits (withdrawals from wallet):
     *   - 'send': Money sent to others
     *   - 'withdraw': Cash withdrawal
     *   - 'fee': Service fees charged
     *   - 'bill_payment': Utility bill payment
     */
    public static function calculateExpectedBalance(int $userId): float
    {
        // Get total credits (money received)
        $creditSql = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM transactions
            WHERE (
                (receiver_id = ? AND type IN ('send', 'refund'))
                OR (sender_id = ? AND type IN ('add_money', 'receive'))
            )
            AND status = 'completed'
        ";
        $credits = Database::fetch($creditSql, [$userId, $userId])['total'] ?? 0;

        // Get total debits (money sent)
        $debitSql = "
            SELECT COALESCE(SUM(amount + COALESCE(fee, 0)), 0) as total
            FROM transactions
            WHERE sender_id = ?
            AND type IN ('send', 'withdraw', 'bill_payment')
            AND status = 'completed'
        ";
        $debits = Database::fetch($debitSql, [$userId])['total'] ?? 0;

        // Expected balance = credits - debits
        // Assume initial balance was 0 (all balance comes from transactions)
        return (float)($credits - $debits);
    }

    /**
     * Get reconciliation status for a specific wallet
     */
    public static function getWalletStatus(int $walletId, int $userId): array
    {
        try {
            // Get last reconciliation result
            $lastCheck = Database::fetch(
                "SELECT * FROM reconciliation_logs 
                 WHERE wallet_id = ? 
                 ORDER BY checked_at DESC 
                 LIMIT 1",
                [$walletId]
            );

            // Get current balance
            $wallet = Database::fetch(
                "SELECT balance FROM wallets WHERE id = ?",
                [$walletId]
            );

            // Get recent transactions
            $recentTxns = Database::all(
                "SELECT transaction_id, amount, type, status, created_at 
                 FROM transactions 
                 WHERE (sender_id = ? OR receiver_id = ?) 
                 ORDER BY created_at DESC 
                 LIMIT 10",
                [$userId, $userId]
            );

            return [
                'wallet_id' => $walletId,
                'current_balance' => $wallet['balance'] ?? 0,
                'last_reconciliation' => $lastCheck,
                'recent_transactions' => $recentTxns
            ];
        } catch (Exception $e) {
            Logger::error('Failed to get wallet status', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Manually reconcile a wallet (admin function)
     * 
     * Call this after fixing the underlying issue that caused the mismatch
     */
    public static function manuallyReconcile(
        int $walletId,
        int $userId,
        float $correctBalance,
        string $reconciliationNote,
        int $adminUserId = 0
    ): bool {
        try {
            Database::beginTransaction();

            // Get wallet
            $wallet = Database::fetch("SELECT * FROM wallets WHERE id = ?", [$walletId]);

            if (!$wallet) {
                throw new Exception('Wallet not found');
            }

            // Update balance
            Database::query(
                "UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?",
                [$correctBalance, $walletId]
            );

            // Log reconciliation
            Database::query(
                "INSERT INTO reconciliation_logs 
                 (wallet_id, user_id, expected_balance, actual_balance, difference, is_reconciled, reconciliation_note, reconciled_at)
                 VALUES (?, ?, ?, ?, ?, 1, ?, NOW())",
                [
                    $walletId,
                    $userId,
                    $correctBalance,
                    $wallet['balance'],
                    $correctBalance - $wallet['balance'],
                    $reconciliationNote
                ]
            );

            // Log in admin action log
            Database::query(
                "INSERT INTO admin_logs (admin_id, target_user_id, action, description)
                 VALUES (?, ?, ?, ?)",
                [
                    $adminUserId,
                    $userId,
                    'manual_reconciliation',
                    "Balance adjusted from {$wallet['balance']} to {$correctBalance}. Reason: {$reconciliationNote}"
                ]
            );

            Database::commit();

            Logger::info('Wallet manually reconciled', [
                'wallet_id' => $walletId,
                'user_id' => $userId,
                'old_balance' => $wallet['balance'],
                'new_balance' => $correctBalance,
                'admin_id' => $adminUserId,
                'note' => $reconciliationNote
            ]);

            return true;
        } catch (Exception $e) {
            Database::rollback();
            Logger::error('Manual reconciliation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
