<?php
/**
 * API Authentication Controller
 * Handles: login, logout, refresh, register
 */

use NepalPay\Helpers\Production\Input;
use NepalPay\Services\AuthService;
use \RuntimeException;

class ApiAuthController extends ApiController {
    
    /**
     * POST /api/auth/login
     * Authenticate user and return tokens
     */
    public function login(): void {
        // Only POST allowed
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Method not allowed', 405);
        }
        
        // Get JSON input or POST data
        $identifier = \NepalPay\Helpers\Production\Input::string('identifier', '');
        $password = \NepalPay\Helpers\Production\Input::string('password', '');
        $deviceInfo = \NepalPay\Helpers\Production\Input::string('device_info', 'Unknown Device');
        
        if (empty($identifier) || empty($password)) {
            $this->jsonError('Identifier and password required', 400);
        }
        
        // Use AuthService for authentication (includes rate limiting, lock checks)
        $user = \NepalPay\Services\AuthService::authenticate($identifier, $password);
        
        if (!$user) {
            // AuthService already logs and handles failed attempt logic
            $this->jsonError('Invalid credentials', 401);
        }
        
        // Generate tokens
        $accessToken = $this->generateToken($user['id'], 'access', $deviceInfo);
        $refreshToken = $this->generateToken($user['id'], 'refresh', $deviceInfo);
        
        // Log successful auth
        \NepalPay\Core\Logger::info('API login successful', ['user_id' => $user['id']]);
        
        $this->jsonSuccess([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => \NepalPay\Core\Config::getInt('API_TOKEN_EXPIRY', 86400),
            'user' => [
                'id' => $user['id'],
                'phone' => $user['phone'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ], 'Login successful');
    }
    
    /**
     * POST /api/auth/refresh
     * Exchange refresh token for new access token
     */
    public function refresh(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Method not allowed', 405);
        }
        
        $refreshToken = \NepalPay\Helpers\Production\Input::bearerToken();
        if (!$refreshToken) {
            $this->jsonError('Refresh token required', 401);
        }
        
        // Validate refresh token
        $user = $this->validateToken($refreshToken, 'refresh');
        if (!$user) {
            $this->jsonError('Invalid or expired refresh token', 401);
        }
        
        // Generate new access token
        $accessToken = $this->generateToken($user['id'], 'access', $user['device_info'] ?? '');
        
        $this->jsonSuccess([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => \NepalPay\Core\Config::getInt('API_TOKEN_EXPIRY', 86400)
        ], 'Token refreshed');
    }
    
    /**
     * POST /api/auth/logout
     * Revoke current token
     */
    public function logout(): void {
        $this->authenticate(); // Requires valid access token
        
        // Revoke token
        $sql = "UPDATE api_tokens SET revoked = 1 WHERE token = ?";
        \NepalPay\Core\Database::query($sql, [$this->token]);
        
        $this->jsonSuccess(null, 'Logged out successfully');
    }
    
    /**
     * Generate API token
     */
    private function generateToken(int $userId, string $type = 'access', string $deviceInfo = ''): string {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + \NepalPay\Core\Config::getInt(
            $type === 'refresh' ? 'API_REFRESH_TOKEN_EXPIRY' : 'API_TOKEN_EXPIRY',
            $type === 'refresh' ? 2592000 : 86400
        ));
        
        try {
            $sql = "INSERT INTO api_tokens 
                    (user_id, token, token_type, device_info, ip_address, expires_at) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            Database::query($sql, [
                $userId,
                $token,
                $type,
                $deviceInfo,
                \NepalPay\Helpers\Production\Input::ip(),
                $expiresAt
            ]);
        } catch (Exception $e) {
            \NepalPay\Core\Logger::error('Failed to create API token', ['error' => $e->getMessage()]);
            throw new RuntimeException('Could not generate token');
        }
        
        return $token;
    }
}
        
        // Get JSON input
        $identifier = Input::string('identifier', '');
        $password = Input::string('password', '');
        $deviceInfo = Input::string('device_info', 'Unknown Device');
        
        if (empty($identifier) || empty($password)) {
            $this->jsonError('Identifier and password required', 400);
        }
        
        // Rate limiting
        $ip = Input::ip();
        $rateKey = "api:login:{$ip}";
        $maxAttempts = Config::getInt('RATE_LIMIT_API', 100);
        $window = Config::getInt('RATE_LIMIT_API_WINDOW', 60);
        
        // Check brute force (simple in-memory check could be added)
        // For now, rely on database-backed RateLimiter if needed
        
        // Find user
        $user = User::findByPhoneOrEmail($identifier);
        
        if (!$user || !User::verifyPassword($user, $password)) {
            // Log failed attempt
            Logger::security('API login failed', [
                'identifier' => $identifier,
                'ip' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            $this->jsonError('Invalid credentials', 401);
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            $this->jsonError('Account disabled', 403);
        }
        
        if ($user['is_frozen']) {
            $this->jsonError('Account frozen', 403);
        }
        
        // Generate tokens
        $accessToken = $this->generateToken($user['id'], 'access', $deviceInfo);
        $refreshToken = $this->generateToken($user['id'], 'refresh', $deviceInfo);
        
        // Log successful auth
        Logger::info('API login successful', ['user_id' => $user['id']]);
        
        $this->jsonSuccess([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => Config::getInt('API_TOKEN_EXPIRY', 86400),
            'user' => [
                'id' => $user['id'],
                'phone' => $user['phone'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ], 'Login successful');
    }
    
    /**
     * POST /api/auth/refresh
     * Exchange refresh token for new access token
     */
    public function refresh(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Method not allowed', 405);
        }
        
        $refreshToken = Input::bearerToken();
        if (!$refreshToken) {
            $this->jsonError('Refresh token required', 401);
        }
        
        // Validate refresh token
        $user = $this->validateToken($refreshToken, 'refresh');
        if (!$user) {
            $this->jsonError('Invalid or expired refresh token', 401);
        }
        
        // Generate new access token
        $accessToken = $this->generateToken($user['id'], 'access', $user['device_info'] ?? '');
        
        $this->jsonSuccess([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => Config::getInt('API_TOKEN_EXPIRY', 86400)
        ], 'Token refreshed');
    }
    
    /**
     * POST /api/auth/logout
     * Revoke current token
     */
    public function logout(): void {
        $this->authenticate(); // Requires valid access token
        
        // Revoke token
        $sql = "UPDATE api_tokens SET revoked = 1 WHERE token = ?";
        Database::query($sql, [$this->token]);
        
        $this->jsonSuccess(null, 'Logged out successfully');
    }
    
    /**
     * Generate API token
     */
    private function generateToken(int $userId, string $type = 'access', string $deviceInfo = ''): string {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + Config::getInt(
            $type === 'refresh' ? 'API_REFRESH_TOKEN_EXPIRY' : 'API_TOKEN_EXPIRY',
            $type === 'refresh' ? 2592000 : 86400
        ));
        
        try {
            $sql = "INSERT INTO api_tokens 
                    (user_id, token, token_type, device_info, ip_address, expires_at) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            Database::query($sql, [
                $userId,
                $token,
                $type,
                $deviceInfo,
                Input::ip(),
                $expiresAt
            ]);
        } catch (Exception $e) {
            Logger::error('Failed to create API token', ['error' => $e->getMessage()]);
            throw new RuntimeException('Could not generate token');
        }
        
        return $token;
    }
}
