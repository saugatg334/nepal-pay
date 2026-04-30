<?php
declare(strict_types=1);

/**
 * EXAMPLE: Enhanced Wallet Controller with Production-Safe Patterns
 * 
 * This shows how to integrate:
 * 1. Idempotency headers
 * 2. Atomic transfers with row locking
 * 3. Structured transaction logging
 * 4. Input validation
 * 5. Authorization checks
 * 
 * COPY THIS PATTERN to your existing WalletController
 */

namespace NepalPay\Controller;

use NepalPay\Services\AtomicTransferService;
use NepalPay\Services\IdempotencyService;
use NepalPay\Services\StructuredLogger;
use NepalPay\Helpers\Production\Input;
use NepalPay\Core\Logger;

class EnhancedWalletControllerExample
{
    /**
     * POST /wallet/transfer
     * 
     * REQUIREMENTS:
     * - X-Idempotency-Key header (UUID, required)
     * - JSON body with toWalletNumber, amount, description
     * 
     * GUARANTEES:
     * - If retried with same idempotency key, returns exact same result
     * - If executed, money is atomically debited from sender and credited to receiver
     * - No double-spending possible, even with concurrent requests
     * 
     * EXAMPLE REQUEST:
     *   POST /api/wallet/transfer HTTP/1.1
     *   Content-Type: application/json
     *   Authorization: Bearer {token}
     *   X-Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000
     *   
     *   {
     *     "toWalletNumber": "NP987654",
     *     "amount": 5000.00,
     *     "description": "Rent payment"
     *   }
     * 
     * EXAMPLE RESPONSE (Success):
     *   HTTP/1.1 200 OK
     *   {
     *     "success": true,
     *     "transaction_id": "TXN-20240425-abc123",
     *     "amount": 5000.00,
     *     "recipient_wallet": "NP987654",
     *     "new_balance": 4999.00,
     *     "timestamp": "2024-04-25T10:30:45Z"
     *   }
     * 
     * EXAMPLE RESPONSE (Failure):
     *   HTTP/1.1 400 Bad Request
     *   {
     *     "success": false,
     *     "error": "Insufficient balance",
     *     "transaction_id": null
     *   }
     */
    public function transfer()
    {
        try {
            // 1. AUTHORIZATION: Verify user is authenticated
            $authenticatedUserId = $this->getCurrentUserId();  // Your auth logic
            if (!$authenticatedUserId) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 401);
            }

            // 2. IDEMPOTENCY KEY: Require X-Idempotency-Key header
            $idempotencyKey = $_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? null;
            if (!$idempotencyKey) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'X-Idempotency-Key header required'
                ], 400);
            }

            // Validate key format (UUID or similar)
            if (!IdempotencyService::validateKey($idempotencyKey)) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Invalid idempotency key format'
                ], 400);
            }

            // 3. INPUT VALIDATION: Get and validate request data
            $data = Input::json();
            
            $toWalletNumber = trim((string)($data['toWalletNumber'] ?? ''));
            if (empty($toWalletNumber) || strlen($toWalletNumber) > 20) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Invalid wallet number'
                ], 400);
            }

            $amount = (float)($data['amount'] ?? 0);
            if ($amount <= 0 || $amount >= 1000000) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Invalid amount'
                ], 400);
            }

            // Check decimal places
            if (strpos((string)$amount, '.') !== false) {
                $decimals = strlen(substr(strrchr((string)$amount, "."), 1));
                if ($decimals > 2) {
                    return $this->jsonResponse([
                        'success' => false,
                        'error' => 'Amount cannot have more than 2 decimal places'
                    ], 400);
                }
            }

            $description = trim((string)($data['description'] ?? ''));
            if (strlen($description) > 255) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Description too long'
                ], 400);
            }

            // 4. EXECUTE ATOMIC TRANSFER
            // This handles:
            // - Idempotency (checks cache, returns if already processed)
            // - Row-level locking (SELECT...FOR UPDATE)
            // - Atomic debit/credit
            // - Transaction logging
            $result = AtomicTransferService::transfer(
                fromUserId: $authenticatedUserId,
                toWalletNumber: $toWalletNumber,
                amount: $amount,
                description: $description,
                idempotencyKey: $idempotencyKey
            );

            // 5. STRUCTURED LOGGING: Log transaction in production format
            StructuredLogger::logTransfer(
                transactionId: $result['transaction_id'],
                senderId: $authenticatedUserId,
                recipientId: $this->getUserIdByWalletNumber($toWalletNumber),
                amount: $amount,
                fee: 0,  // Adjust based on your fee model
                senderBalanceBefore: $result['new_balance'] + $amount,  // Calculate from result
                senderBalanceAfter: $result['new_balance'],
                description: $description
            );

            // 6. RETURN SUCCESS RESPONSE
            return $this->jsonResponse([
                'success' => true,
                'transaction_id' => $result['transaction_id'],
                'amount' => $amount,
                'recipient_wallet' => $toWalletNumber,
                'new_balance' => $result['new_balance'],
                'timestamp' => $result['timestamp']
            ], 200);

        } catch (\Exception $e) {
            // 7. ERROR HANDLING: Log failure and return error
            Logger::error('Transfer failed', [
                'user_id' => $authenticatedUserId ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            StructuredLogger::logFailure(
                transactionId: 'FAILED-' . time(),
                userId: $authenticatedUserId ?? 0,
                action: 'transfer',
                amount: $amount ?? 0,
                reason: $e->getMessage()
            );

            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'transaction_id' => null
            ], 400);
        }
    }

    /**
     * GET /wallet/balance
     * 
     * IDEMPOTENT: Safe to call multiple times (GET request)
     * No idempotency key needed for GET requests
     */
    public function getBalance()
    {
        try {
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }

            $wallet = $this->getWalletByUserId($userId);
            if (!$wallet) {
                return $this->jsonResponse(['success' => false, 'error' => 'Wallet not found'], 404);
            }

            return $this->jsonResponse([
                'success' => true,
                'balance' => $wallet['balance'],
                'currency' => 'NPR',
                'wallet_number' => $wallet['wallet_number'],
                'updated_at' => $wallet['updated_at']
            ], 200);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /wallet/history
     * 
     * IDEMPOTENT: Safe to call multiple times
     * Returns transaction history (immutable records)
     */
    public function getTransactionHistory()
    {
        try {
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }

            $page = max(1, (int)Input::int('page', 1));
            $perPage = min(100, (int)Input::int('limit', 20));
            $offset = ($page - 1) * $perPage;

            // Get transactions (never modified after creation)
            $transactions = $this->getTransactionsByUserId($userId, $offset, $perPage);
            
            return $this->jsonResponse([
                'success' => true,
                'transactions' => $transactions,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $this->countTransactionsByUserId($userId)
            ], 200);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============== HELPER METHODS ==============
    
    private function getCurrentUserId(): ?int
    {
        // Your authentication logic here
        return $_SESSION['user_id'] ?? null;
    }

    private function getWalletByUserId(int $userId): ?array
    {
        // Your database query here
        return null;
    }

    private function getUserIdByWalletNumber(string $walletNumber): ?int
    {
        // Your database query here
        return null;
    }

    private function getTransactionsByUserId(int $userId, int $offset, int $limit): array
    {
        // Your database query here
        return [];
    }

    private function countTransactionsByUserId(int $userId): int
    {
        // Your database query here
        return 0;
    }

    private function jsonResponse(array $data, int $code = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data);
        exit;
    }
}
