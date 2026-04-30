<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Config;
use NepalPay\Core\Logger;
use Exception;

/**
 * Token Encryption Service
 * 
 * WHY: API tokens stored in the database must be encrypted at rest
 * to prevent token theft in case of database breach.
 * 
 * Security Model:
 * - Tokens are encrypted using AES-256-CBC
 * - Lookup uses HMAC-SHA256 (one-way, can't be reversed)
 * - IV stored alongside encrypted token
 * - Master key from environment (APP_KEY)
 * - Tokens can be rotated/re-encrypted if key changes
 */
class TokenEncryptionService
{
    /**
     * @var string Encryption cipher
     */
    private const CIPHER = 'aes-256-cbc';
    
    /**
     * @var int IV length for cipher
     */
    private const IV_LENGTH = 16;
    
    /**
     * @var string HMAC algorithm for token lookup
     */
    private const HMAC_ALGO = 'sha256';
    
    /**
     * Encrypt a token
     * 
     * @param string $token Plain text token
     * @return array [encrypted_token, iv, token_hash]
     * @throws Exception
     */
    public static function encrypt(string $token): array
    {
        $key = self::getEncryptionKey();
        
        // Generate random IV
        $iv = random_bytes(self::IV_LENGTH);
        if ($iv === false) {
            throw new Exception('Failed to generate IV');
        }
        
        // Encrypt token
        $encrypted = openssl_encrypt(
            $token,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($encrypted === false) {
            throw new Exception('Token encryption failed: ' . openssl_error_string());
        }
        
        // Generate HMAC for lookup (one-way, cannot decrypt to get token)
        $tokenHash = hash_hmac(self::HMAC_ALGO, $token, $key);
        
        return [
            'encrypted' => base64_encode($encrypted),
            'iv' => bin2hex($iv),
            'hash' => $tokenHash
        ];
    }
    
    /**
     * Decrypt a token
     * 
     * @param string $encryptedToken Base64 encoded encrypted token
     * @param string $iv Hex encoded IV
     * @return string Decrypted token
     * @throws Exception
     */
    public static function decrypt(string $encryptedToken, string $iv): string
    {
        $key = self::getEncryptionKey();
        
        $ivBytes = hex2bin($iv);
        if ($ivBytes === false) {
            throw new Exception('Invalid IV format');
        }
        
        $encrypted = base64_decode($encryptedToken, true);
        if ($encrypted === false) {
            throw new Exception('Invalid encrypted token format');
        }
        
        $decrypted = openssl_decrypt(
            $encrypted,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $ivBytes
        );
        
        if ($decrypted === false) {
            throw new Exception('Token decryption failed: ' . openssl_error_string());
        }
        
        return $decrypted;
    }
    
    /**
     * Generate HMAC hash for token lookup
     * 
     * @param string $token Token to hash
     * @return string HMAC hash
     */
    public static function hashToken(string $token): string
    {
        $key = self::getEncryptionKey();
        return hash_hmac(self::HMAC_ALGO, $token, $key);
    }
    
    /**
     * Verify token against hash
     * 
     * @param string $token Token to verify
     * @param string $hash Expected hash
     * @return bool True if matches
     */
    public static function verifyHash(string $token, string $hash): bool
    {
        $computedHash = self::hashToken($token);
        return hash_equals($hash, $computedHash);
    }
    
    /**
     * Rotate encryption key (re-encrypt all tokens)
     * 
     * WARNING: This is a sensitive operation
     * Should only be done when master key changes
     * 
     * @param string $oldKey Previous encryption key
     * @return int Number of tokens re-encrypted
     * @throws Exception
     */
    public static function rotateKey(string $oldKey, string $newKey): int
    {
        if (!class_exists('Database')) {
            throw new Exception('Database class not available');
        }
        
        try {
            // Fetch all encrypted tokens
            $sql = "SELECT id, token_encrypted, token_iv, token_hash FROM api_tokens WHERE revoked = 0";
            $tokens = \Database::fetchAll($sql);
            
            $rotated = 0;
            foreach ($tokens as $token) {
                // Decrypt with old key
                $ivBytes = hex2bin($token['token_iv']);
                $encrypted = base64_decode($token['token_encrypted'], true);
                
                $plainToken = openssl_decrypt(
                    $encrypted,
                    self::CIPHER,
                    $oldKey,
                    OPENSSL_RAW_DATA,
                    $ivBytes
                );
                
                if ($plainToken === false) {
                    Logger::error('Failed to decrypt token during rotation', ['token_id' => $token['id']]);
                    continue;
                }
                
                // Re-encrypt with new key
                $iv = random_bytes(self::IV_LENGTH);
                $newEncrypted = openssl_encrypt($plainToken, self::CIPHER, $newKey, OPENSSL_RAW_DATA, $iv);
                $newHash = hash_hmac(self::HMAC_ALGO, $plainToken, $newKey);
                
                // Update database
                $sql = "UPDATE api_tokens SET token_encrypted = ?, token_iv = ?, token_hash = ? WHERE id = ?";
                \Database::query($sql, [
                    base64_encode($newEncrypted),
                    bin2hex($iv),
                    $newHash,
                    $token['id']
                ]);
                
                $rotated++;
            }
            
            Logger::info('Encryption key rotation completed', ['rotated_tokens' => $rotated]);
            return $rotated;
            
        } catch (Exception $e) {
            Logger::error('Key rotation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Get encryption key from configuration
     * 
     * @return string 32-byte encryption key
     * @throws Exception
     */
    private static function getEncryptionKey(): string
    {
        // Get master key from environment
        $masterKey = Config::getString('APP_KEY', '');
        
        if (empty($masterKey)) {
            throw new Exception('Encryption key not configured (APP_KEY missing)');
        }
        
        // Derive 32-byte key from master key using HKDF
        // This ensures we have a properly sized key even if APP_KEY is not exactly 32 bytes
        $salt = Config::getString('ENCRYPTION_KEY_SALT', 'nepalpay-token-encryption-salt');
        $info = 'api-token-encryption';
        
        // Simple HKDF-like derivation (for production, use proper HKDF)
        $key = hash_hkdf('sha256', $masterKey, 32, $info, $salt);
        
        // Ensure key is exactly 32 bytes
        if (strlen($key) !== 32) {
            // Fallback: hash to get 32 bytes
            $key = hash('sha256', $masterKey, true);
        }
        
        return $key;
    }
    
    /**
     * Validate that encryption key is properly configured
     * 
     * @return array Validation result
     */
    public static function validateKey(): array
    {
        $result = [
            'valid' => false,
            'warnings' => [],
            'errors' => []
        ];
        
        try {
            $key = self::getEncryptionKey();
            
            if (strlen($key) !== 32) {
                $result['errors'][] = 'Encryption key must be 32 bytes';
                return $result;
            }
            
            // Test encryption/decryption cycle
            $testToken = 'test-token-' . bin2hex(random_bytes(16));
            $encrypted = self::encrypt($testToken);
            $decrypted = self::decrypt($encrypted['encrypted'], $encrypted['iv']);
            
            if ($decrypted !== $testToken) {
                $result['errors'][] = 'Encryption/decryption test failed';
                return $result;
            }
            
            $result['valid'] = true;
            
            // Check for weak key
            $masterKey = Config::getString('APP_KEY', '');
            if (strlen($masterKey) < 32) {
                $result['warnings'][] = 'APP_KEY is less than 32 characters. Consider using a longer key for better security.';
            }
            
            // Check if using default salt
            $salt = Config::getString('ENCRYPTION_KEY_SALT', 'nepalpay-token-encryption-salt');
            if ($salt === 'nepalpay-token-encryption-salt') {
                $result['warnings'][] = 'Using default encryption salt. Change ENCRYPTION_KEY_SALT in .env for production.';
            }
            
        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
        }
        
        return $result;
    }
}
