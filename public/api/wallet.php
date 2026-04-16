<?php
/**
 * Wallet API - Production-Grade Wallet Operations Endpoint
 * 
 * Handles credit, debit, and transfer operations via JSON POST
 * Uses WalletService with full ACID transaction support
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'failed',
        'message' => 'Method not allowed. Use POST.',
        'data' => []
    ]);
    exit;
}

// Get raw POST data
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'status' => 'failed',
        'message' => 'Invalid JSON format',
        'data' => []
    ]);
    exit;
}

// Validate action parameter
$action = isset($input['action']) ? strtolower(trim($input['action'])) : '';

if (!in_array($action, ['credit', 'debit', 'transfer', 'balance'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'failed',
        'message' => 'Invalid action. Use: credit, debit, transfer, or balance',
        'data' => []
    ]);
    exit;
}

// Validate required fields
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$referenceId = isset($input['reference_id']) ? trim($input['reference_id']) : null;

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'failed',
        'message' => 'Invalid user_id',
        'data' => []
    ]);
    exit;
}

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'failed',
        'message' => 'Invalid amount',
        'data' => []
    ]);
    exit;
}

// Generate reference_id if not provided
if (empty($referenceId)) {
    $referenceId = strtoupper($action) . '_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4));
}

// Include WalletService
$basePath = dirname(__DIR__);
require_once $basePath . '/app/config/database.php';
require_once $basePath . '/app/services/WalletService.php';
require_once $basePath . '/app/services/LedgerService.php';

try {
    $walletService = new WalletService();
    $result = null;
    
    switch ($action) {
        case 'balance':
            $balance = $walletService->getBalance($userId);
            echo json_encode([
                'status' => 'success',
                'message' => 'Balance retrieved',
                'data' => [
                    'user_id' => $userId,
                    'balance' => $balance
                ]
            ]);
            break;
            
        case 'credit':
            $result = $walletService->credit($userId, $amount, $referenceId);
            break;
            
        case 'debit':
            $result = $walletService->debit($userId, $amount, $referenceId);
            break;
            
        case 'transfer':
            $toUserId = isset($input['to_user_id']) ? intval($input['to_user_id']) : 0;
            
            if ($toUserId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'failed',
                    'message' => 'Invalid to_user_id',
                    'data' => []
                ]);
                exit;
            }
            
            $result = $walletService->transfer($userId, $toUserId, $amount);
            break;
    }
    
    if ($result) {
        $status = $result['success'] ? 'success' : 'failed';
        if (isset($result['duplicate'])) {
            $status = 'duplicate';
        }
        
        echo json_encode([
            'status' => $status,
            'message' => $result['message'] ?? '',
            'data' => $result['data'] ?? []
        ]);
    } else {
        echo json_encode([
            'status' => 'failed',
            'message' => 'Operation failed',
            'data' => []
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Wallet API error: ' . $e->getMessage());
    
    echo json_encode([
        'status' => 'failed',
        'message' => 'Server error: ' . $e->getMessage(),
        'data' => []
    ]);
}