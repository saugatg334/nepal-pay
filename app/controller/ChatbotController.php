<?php
declare(strict_types=1);
namespace NepalPay\Controller;
use NepalPay\Services\ChatbotService;
use NepalPay\Helpers\Production\RateLimiter;
use NepalPay\Core\Logger;

/**
 * Chatbot API Controller
 * Secure AJAX endpoint for NepalPay Sathi
 */
class ChatbotController
{
    /** Maximum messages per minute per user */
    private const RATE_LIMIT = 30;
    
    /**
     * Handle chat message via AJAX
     */
    public function message(): void
    {
        // Check session auth
        $userId = $_SESSION['user_id'] ?? null;
        
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Method not allowed', 405);
        }
        
        // CSRF token validation
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!\NepalPay\Helpers\CSRF::validateToken($token)) {
            Logger::security('Chatbot CSRF failure', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            $this->jsonError('Invalid token', 403);
        }
        
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rateKey = "chatbot:user:" . ($userId ?? $ip);
        $remaining = RateLimiter::remaining($rateKey, self::RATE_LIMIT, 60);
        if ($remaining <= 0) {
            Logger::warning('Chatbot rate limit hit', ['user_id' => $userId, 'ip' => $ip]);
            $this->jsonError('Too many messages. Please try again in a minute.', 429);
        }
        RateLimiter::hit($rateKey);
        
        // Get and validate message
        $message = trim($_POST['message'] ?? '');
        if (empty($message) || strlen($message) > 500) {
            $this->jsonError('Message must be 1–500 characters', 400);
        }
        
        // XSS sanitize - strip tags, allow basic formatting
        $message = strip_tags($message);
        
        // Process through chatbot service
        $service = new ChatbotService($userId ? (int)$userId : null);
        $response = $service->processMessage($message);
        
        // Return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'response' => $response['text'],
            'meta' => [
                'intent' => $response['intent'],
                'language' => $response['language'],
                'sentiment' => $response['sentiment'],
                'typing_ms' => $response['typing_ms'],
            ],
            'quick_replies' => $response['quick_replies'] ?? [],
            'actions' => $response['actions'] ?? [],
            'rate_remaining' => $remaining - 1,
        ]);
        exit;
    }
    
    /**
     * Get chat history for current user
     */
    public function history(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $this->jsonError('Authentication required', 401);
        }
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        
        try {
            $messages = \Database::fetchAll(
                "SELECT user_message, bot_response, intent_detected, sentiment, created_at 
                 FROM chatbot_conversations 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT ? OFFSET ?",
                [$userId, $limit, $offset]
            );
            
            $total = \Database::fetch(
                "SELECT COUNT(*) as cnt FROM chatbot_conversations WHERE user_id = ?",
                [$userId]
            )['cnt'] ?? 0;
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'messages' => array_reverse($messages),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit),
                ],
            ]);
        } catch (\Exception $e) {
            Logger::error('Chatbot history failed', ['error' => $e->getMessage()]);
            $this->jsonError('Failed to load history', 500);
        }
        exit;
    }
    
    /**
     * Download chat history as text file
     */
    public function download(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $this->jsonError('Authentication required', 401);
        }
        
        $messages = \Database::fetchAll(
            "SELECT user_message, bot_response, created_at 
             FROM chatbot_conversations 
             WHERE user_id = ? 
             ORDER BY created_at ASC",
            [$userId]
        );
        
        $output = "NepalPay Sathi — Chat History\n";
        $output .= "User ID: " . $userId . "\n";
        $output .= "Downloaded: " . date('Y-m-d H:i:s') . "\n";
        $output .= str_repeat("=", 50) . "\n\n";
        
        foreach ($messages as $msg) {
            $time = date('Y-m-d H:i', strtotime($msg['created_at']));
            $output .= "[{$time}] You: {$msg['user_message']}\n";
            $output .= "[{$time}] Bot: {$msg['bot_response']}\n\n";
        }
        
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="nepalpay-chat-' . date('Ymd') . '.txt"');
        echo $output;
        exit;
    }
    
    /**
     * Admin: view support tickets
     */
    public function tickets(): void
    {
        $this->requireAdmin();
        
        $status = $_GET['status'] ?? 'open';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $tickets = \Database::fetchAll(
            "SELECT t.*, u.full_name, u.phone, u.email 
             FROM support_tickets t
             JOIN users u ON t.user_id = u.id
             WHERE t.status = ?
             ORDER BY t.priority DESC, t.created_at DESC
             LIMIT ? OFFSET ?",
            [$status, $limit, $offset]
        );
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'tickets' => $tickets,
            'page' => $page,
        ]);
        exit;
    }
    
    /**
     * Admin: update ticket status
     */
    public function ticketUpdate(): void
    {
        $this->requireAdmin();
        
        $id = (int)($_POST['ticket_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $notes = strip_tags($_POST['resolution_notes'] ?? '');
        
        if (!in_array($status, ['open','in_progress','waiting','resolved','closed'])) {
            $this->jsonError('Invalid status', 400);
        }
        
        \Database::query(
            "UPDATE support_tickets SET status = ?, resolution_notes = ?, resolved_at = IF(? = 'resolved', NOW(), resolved_at), updated_at = NOW(), assigned_to = ? WHERE id = ?",
            [$status, $notes, $status, $_SESSION['user_id'] ?? null, $id]
        );
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    
    private function requireAdmin(): void
    {
        if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            $this->jsonError('Admin access required', 403);
        }
    }
    
    private function jsonError(string $msg, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $msg, 'code' => $code]);
        exit;
    }
}
