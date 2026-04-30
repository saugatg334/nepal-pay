    /**
     * Validate bearer token and load user
     * Also performs rate limiting for API endpoint access
     */
    protected function authenticate(): void
    {
        // Request ID for replay attack protection
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? null;
        $timestamp = $_SERVER['HTTP_X_REQUEST_TIMESTAMP'] ?? time();
        
        if ($requestId && class_exists('NepalPay\\Services\\ReplayProtectionService')) {
            if (!ReplayProtectionService::validateRequest($requestId, (int)$timestamp)) {
                Logger::security('Replay attack detected', [
                    'request_id' => $requestId,
                    'ip' => Input::ip()
                ]);
                $this->jsonError('Request validation failed', 403);
            }
        }
        
        // Rate limit check (IP-based for unauthenticated requests)
        $ip = Input::ip();
        $rateKey = "api:access:{$ip}";
        $maxAttempts = Config::getInt('RATE_LIMIT_API', 100);
        $windowSeconds = Config::getInt('RATE_LIMIT_API_WINDOW', 60);
        
        // Simple rate limit check using RateLimiter helper
        if (class_exists('NepalPay\\Helpers\\Production\\RateLimiter')) {
            $remaining = NepalPay\Helpers\Production\RateLimiter::remaining($rateKey, $maxAttempts, $windowSeconds);
            if ($remaining <= 0) {
                Logger::warning('API rate limit exceeded', ['ip' => $ip]);
                $this->jsonError('Too many requests. Please try again later.', 429);
            }
            // Record this attempt
            NepalPay\Helpers\Production\RateLimiter::hit($rateKey);
        }
        
        $token = Input::bearerToken();

        if (!$token) {
            $this->jsonError('Authentication required', 401);
        }

        $this->token = $token;
        $user = $this->validateToken($token);

        if (!$user) {
            Logger::security('Invalid API token used', [
                'token_prefix' => substr($token, 0, 8),
                'ip' => $ip
            ]);
            $this->jsonError('Invalid or expired token', 401);
        }

        $this->user = $user;
        
        // Update token last_used_at
        try {
            $tokenHash = \NepalPay\Services\TokenEncryptionService::hashToken($token);
            $sql = "UPDATE api_tokens SET last_used_at = NOW() WHERE token_hash = ?";
            Database::query($sql, [$tokenHash]);
        } catch (\Exception $e) {
            // Non-critical
        }
    }