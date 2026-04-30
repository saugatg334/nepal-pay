<?php
/**
 * NepalPay Sathi — Standalone Chatbot API Endpoint
 * 
 * This file can be called directly via AJAX:
 * POST /public/api/chatbot_api.php
 * 
 * Headers Required:
 * - Content-Type: application/x-www-form-urlencoded (or multipart/form-data)
 * - X-CSRF-Token: <token> (if not sent as form field)
 * 
 * Parameters:
 * - message: User's chat message (required, max 500 chars)
 * - csrf_token: CSRF protection token (required)
 * - action: 'message' | 'history' | 'download' (default: message)
 * 
 * Response: JSON
 */
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use NepalPay\Controller\ChatbotController;

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Only accept POST for write operations
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST required', 'code' => 405]);
    exit;
}

// Route to controller
try {
    $controller = new ChatbotController();
    $action = $_POST['action'] ?? $_GET['action'] ?? 'message';
    
    match ($action) {
        'message' => $controller->message(),
        'history' => $controller->history(),
        'download' => $controller->download(),
        'tickets' => $controller->tickets(),
        'ticket_update' => $controller->ticketUpdate(),
        default => throw new Exception('Unknown action: ' . $action),
    };
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'code' => 500
    ]);
    exit;
}
