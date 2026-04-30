<?php
/**
 * API Transactions Controller
 * Handles: detailed transaction history, search, filters
 */

use NepalPay\Helpers\Production\Input;

class ApiTransactionController extends ApiController {
    
    /**
     * GET /api/transactions
     * Get user's transactions with optional filters
     */
    public function index(): void {
        $this->authenticate();
        
        $page = Input::int('page', 1);
        $limit = Input::int('limit', 20);
        $type = Input::string('type', '');
        $status = Input::string('status', '');
        
        $offset = ($page - 1) * $limit;
        
        $where = ["(sender_id = ? OR receiver_id = ?)"];
        $params = [$this->getUserId(), $this->getUserId()];
        
        if ($type) {
            $where[] = "type = ?";
            $params[] = $type;
        }
        
        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
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
        $countSql = "SELECT COUNT(*) as total FROM transactions 
                     WHERE {$whereClause}";
        $countResult = Database::fetch($countSql, array_slice($params, 0, -2));
        $total = $countResult['total'] ?? 0;
        
        $this->jsonSuccess([
            'transactions' => $transactions,
            'filters' => [
                'type' => $type,
                'status' => $status
            ],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * GET /api/transactions/:id
     * Get single transaction details
     */
    public function show(): void {
        $this->authenticate();
        
        $transactionId = Input::string('id', '');
        if (empty($transactionId)) {
            $this->jsonError('Transaction ID required', 400);
        }
        
        $sql = "SELECT * FROM transactions 
                WHERE transaction_id = ? 
                AND (sender_id = ? OR receiver_id = ?)";
        $transaction = Database::fetch($sql, [
            $transactionId,
            $this->getUserId(),
            $this->getUserId()
        ]);
        
        if (!$transaction) {
            $this->jsonError('Transaction not found', 404);
        }
        
        $this->jsonSuccess(['transaction' => $transaction]);
    }
}
