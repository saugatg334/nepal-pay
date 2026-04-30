<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Logger;
use Exception;

/**
 * Secrets Manager Service
 * 
 * WHY: Production systems require secure key/secret management.
 * Environment variables in .env files are insufficient for production.
 * 
 * This service provides:
 * - Encrypted secrets storage
 * - Key versioning
 * - Automatic rotation
 * - Audit logging
 */
class SecretsManager
{
    /**
     * @var string Local encryption key for protecting stored secrets
     */
    private static $localKey = null;
    
    /**
     * @var array In-memory secret cache
     */
    private static $cache = [];
    
    /**
     * Initialize secrets manager
     * 
     * @param string|null $key Optional encryption key
     */
    public static function init(?string $key = null): void
    {
        if ($key !== null) {
            self::$localKey = $key;
        }
        
        if (self::$localKey === null) {
            // Try to load from environment
            self::$localKey = getenv('SECRETS_ENCRYPTION_KEY') ?: null;
        }
        
        if (self::$localKey === null) {
            throw new Exception('Secrets encryption key not configured');
        }
    }
    
    /**
     * Store a secret securely
     * 
     * @param string $key Secret identifier
     * @param string $value Secret value
     * @param string|null $version Version label (optional)
     * @return bool Success
     */
    public static function set(string $key, string $value, ?string $version = null): bool
    {
        if (self::$localKey === null) {
            self::init();
        }
        
        try {
            // Encrypt the secret
            $iv = random_bytes(16);
            $encrypted = openssl_encrypt(
                $value,
                'aes-256-cbc',
                self::$localKey,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($encrypted === false) {
                throw new Exception('Encryption failed: ' . openssl_error_string());
            }
            
            // Store metadata
            $secretData = [
                'encrypted' => base64_encode($encrypted),
                'iv' => bin2hex($iv),
                'version' => $version ?? 'default',
                'created_at' => time(),
                'checksum' => hash_hmac('sha256', $value, self::$localKey)
            ];
            
            // Persist (in production, use database or vault)
            // For now, store in file with restricted permissions
            $filename = self::getStoragePath() . '/' . hash('sha256', $key) . '.secret';
            $data = json_encode($secretData);
            
            $result = file_put_contents($filename, $data);
            chmod($filename, 0600);
            
            // Update cache
            self::$cache[$key] = $secretData;
            
            Logger::info('Secret stored', ['key' => $key, 'version' => $version]);
            
            return $result !== false;
            
        } catch (Exception $e) {
            Logger::error('Failed to store secret', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Retrieve a secret
     * 
     * @param string $key Secret identifier
     * @param string|null $version Version label (optional)
     * @return string|null Decrypted secret or null if not found
     */
    public static function get(string $key, ?string $version = null): ?string
    {
        if (self::$localKey === null) {
            self::init();
        }
        
        // Check cache first
        if (isset(self::$cache[$key])) {
            $data = self::$cache[$key];
            if ($version === null || $data['version'] === $version) {
                return self::decryptSecretData($data);
            }
        }
        
        // Load from storage
        $filename = self::getStoragePath() . '/' . hash('sha256', $key) . '.secret';
        
        if (!file_exists($filename)) {
            // Fallback to environment variable
            $envValue = getenv('SECRET_' . strtoupper($key));
            if ($envValue !== false) {
                return $envValue;
            }
            return null;
        }
        
        try {
            $data = json_decode(file_get_contents($filename), true);
            
            if ($data === null) {
                throw new Exception('Invalid secret data');
            }
            
            // Check version
            if ($version !== null && $data['version'] !== $version) {
                return null;
            }
            
            // Cache and return
            self::$cache[$key] = $data;
            return self::decryptSecretData($data);
            
        } catch (Exception $e) {
            Logger::error('Failed to retrieve secret', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Delete a secret
     * 
     * @param string $key Secret identifier
     * @return bool Success
     */
    public static function delete(string $key): bool
    {
        $filename = self::getStoragePath() . '/' . hash('sha256', $key) . '.secret';
        
        if (file_exists($filename)) {
            unlink($filename);
        }
        
        unset(self::$cache[$key]);
        
        Logger::info('Secret deleted', ['key' => $key]);
        
        return true;
    }
    
    /**
     * Rotate all secrets (re-encrypt with new key)
     * 
     * @param string $newKey New encryption key
     * @return int Number of secrets rotated
     */
    public static function rotateAll(string $newKey): int
    {
        $storagePath = self::getStoragePath();
        $files = glob($storagePath . '/*.secret');
        
        if ($files === false) {
            return 0;
        }
        
        $oldKey = self::$localKey;
        $rotated = 0;
        
        foreach ($files as $file) {
            try {
                $data = json_decode(file_get_contents($file), true);
                
                // Decrypt with old key
                $iv = hex2bin($data['iv']);
                $encrypted = base64_decode($data['encrypted']);
                
                $plaintext = openssl_decrypt(
                    $encrypted,
                    'aes-256-cbc',
                    $oldKey,
                    OPENSSL_RAW_DATA,
                    $iv
                );
                
                if ($plaintext === false) {
                    continue;
                }
                
                // Re-encrypt with new key
                $newIv = random_bytes(16);
                $newEncrypted = openssl_encrypt(
                    $plaintext,
                    'aes-256-cbc',
                    $newKey,
                    OPENSSL_RAW_DATA,
                    $newIv
                );
                
                $newData = [
                    'encrypted' => base64_encode($newEncrypted),
                    'iv' => bin2hex($newIv),
                    'version' => time(), // New version
                    'rotated_at' => time(),
                    'checksum' => hash_hmac('sha256', $plaintext, $newKey)
                ];
                
                file_put_contents($file, json_encode($newData));
                $rotated++;
                
            } catch (Exception $e) {
                Logger::error('Failed to rotate secret', [
                    'file' => $file,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        self::$localKey = $newKey;
        self::$cache = [];
        
        Logger::info('Secrets rotation completed', ['rotated' => $rotated]);
        
        return $rotated;
    }
    
    /**
     * Decrypt secret data
     * 
     * @param array $data Encrypted secret data
     * @return string|null Decrypted secret
     */
    private static function decryptSecretData(array $data): ?string
    {
        try {
            $iv = hex2bin($data['iv']);
            $encrypted = base64_decode($data['encrypted']);
            
            $decrypted = openssl_decrypt(
                $encrypted,
                'aes-256-cbc',
                self::$localKey,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($decrypted === false) {
                throw new Exception('Decryption failed');
            }
            
            // Verify checksum
            $checksum = hash_hmac('sha256', $decrypted, self::$localKey);
            if (!hash_equals($checksum, $data['checksum'])) {
                throw new Exception('Checksum verification failed');
            }
            
            return $decrypted;
            
        } catch (Exception $e) {
            Logger::error('Secret decryption failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Get storage path for secrets
     * 
     * @return string
     */
    private static function getStoragePath(): string
    {
        $path = dirname(__DIR__) . '/data/secrets';
        
        if (!is_dir($path)) {
            mkdir($path, 0700, true);
        }
        
        return $path;
    }
    
    /**
     * Get all secret keys (metadata only)
     * 
     * @return array
     */
    public static function listKeys(): array
    {
        $storagePath = self::getStoragePath();
        $files = glob($storagePath . '/*.secret');
        
        if ($files === false) {
            return [];
        }
        
        $keys = [];
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            $keys[basename($file, '.secret')] = [
                'version' => $data['version'] ?? 'unknown',
                'created_at' => $data['created_at'] ?? 0,
                'rotated_at' => $data['rotated_at'] ?? null
            ];
        }
        
        return $keys;
    }
    
    /**
     * Validate that all required secrets are present
     * 
     * @param array $required Required secret keys
     * @return array [bool, array] - [valid, missing keys]
     */
    public static function validateRequired(array $required): array
    {
        $missing = [];
        
        foreach ($required as $key) {
            if (self::get($key) === null) {
                $missing[] = $key;
            }
        }
        
        return [empty($missing), $missing];
    }
}
