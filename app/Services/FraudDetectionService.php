<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Config;
use NepalPay\Core\Logger;
use \Database;
use Exception;

/**
 * Fraud Detection Service
 * 
 * WHY: Real-time fraud detection is critical for financial systems.
 * This service provides:
 * - Velocity checks (transactions per time window)
 * - Amount threshold monitoring
 * - Suspicious pattern detection
 * - Risk scoring for transactions
 * 
 * Risk Levels:
 * - low: Normal transaction, proceed
 * - medium: May require additional verification (PIN/OTP)
 * - high: Likely fraudulent, require manual review or block
 * - critical: Block transaction immediately
 */
class FraudDetectionService
{
    /**
     * Assess fraud risk for a transaction
     * 
     * @param int $userId User ID
     * @param float $amount Transaction amount
     * @param string $recipientType 'user' or 'merchant'
     * @param int|null $recipientId Recipient user ID
     * @param array $context Additional context (IP, device, etc.)
     * @return array Risk assessment result
     */
    public static function assess(int $userId, float $amount, string $recipientType = 'user', ?int $recipientId = null, array $context = []): array
    {
        $riskScore = 0;
        $reasons = [];
        
        // Get user's recent transaction patterns
        $userStats = self::getUserTransactionStats($userId);
        
        // 1. Velocity check - too many transactions in short time
        $recentCount = $userStats['last_hour_count'];
        $maxPerHour = Config::getInt('FRAUD_MAX_TXNS_PER_HOUR', 10);
        if ($recentCount >= $maxPerHour) {
            $riskScore += 40;
            $reasons[] = "High transaction velocity: {$recentCount} in last hour (max: {$maxPerHour})";
        } elseif ($recentCount >= $maxPerHour * 0.7) {
            $riskScore += 20;
            $reasons[] = "Elevated transaction velocity: {$recentCount} in last hour";
        }
        
        // 2. Amount threshold checks
        $dailyTotal = $userStats['today_total'];
        $maxDailyAmount = Config::getInt('FRAUD_MAX_DAILY_AMOUNT', 50000);
        if ($dailyTotal + $amount > $maxDailyAmount) {
            $riskScore += 50;
            $reasons[] = "Daily limit exceeded: {$dailyTotal} + {$amount} > {$maxDailyAmount}";
        }
        
        // 3. Large single transaction
        $maxSingleAmount = Config::getInt('FRAUD_MAX_SINGLE_AMOUNT', 25000);
        if ($amount > $maxSingleAmount) {
            $riskScore += 30;
            $reasons[] = "Large transaction amount: {$amount} > {$maxSingleAmount}";
        }
        
        // 4. Rapid successive transactions (within 5 minutes)
        $rapidCount = $userStats['last_5min_count'];
        if ($rapidCount >= 3) {
            $riskScore += 35;
            $reasons[] = "Rapid successive transactions: {$rapidCount} in 5 minutes";
        }
        
        // 5. Unusual time detection (transactions at unusual hours)
        if (self::isUnusualTime()) {
            $riskScore += 15;
            $reasons[] = "Transaction at unusual hour";
        }
        
        // 6. New recipient check (first time sending to this recipient)
        if ($recipientId && self::isNewRecipient($userId, $recipientId)) {
            $riskScore += 10;
            $reasons[] = "First transaction to this recipient";
        }
        
        // 7. Round amount check (potential fraudulent pattern)
        if ($amount >= 1000 && $amount % 1000 == 0) {
            $riskScore += 5;
            $reasons[] = "Round amount transaction (potential pattern)";
        }
        
        // 8. Amount velocity (sudden large increase)
        $avgAmount = $userStats['avg_amount'];
        if ($avgAmount > 0 && $amount > $avgAmount * 5) {
            $riskScore += 25;
            $reasons[] = "Amount significantly higher than average: {$amount} vs avg {$avgAmount}";
        }
        
        // 9. Check if user account is new (< 24 hours)
        if (self::isNewAccount($userId)) {
            $riskScore += 20;
            $reasons[] = "Transaction from new account";
        }
        
        // Determine risk level
        $riskLevel = 'low';
        if ($riskScore >= 70) {
            $riskLevel = 'critical';
        } elseif ($riskScore >= 50) {
            $riskLevel = 'high';
        } elseif ($riskScore >= 30) {
            $riskLevel = 'medium';
        }
        
        // Log fraud assessment
        self::logAssessment($userId, $amount, $riskLevel, $riskScore, $reasons, $context);
        
        // Check if IP is known for fraud
        $ip = $context['ip'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (self::isBlacklistedIP($ip)) {
            $riskLevel = 'critical';
            $riskScore += 100;
            $reasons[] = "IP address is blacklisted: {$ip}";
        }
        
        return [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'should_block' => $riskScore >= 70,
            'requires_verification' => $riskScore >= 30,
            'reasons' => $reasons,
            'recommendation' => self::getRecommendation($riskLevel, $riskScore)
        ];
    }
    
    /**
     * Get user's transaction statistics
     */
    private static function getUserTransactionStats(int $userId): array
    {
        $now = date('Y-m-d H:i:s');
        $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $fiveMinAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $todayStart = date('Y-m-d 00:00:00');
        $accountCreated = self::getAccountCreationTime($userId);
        
        try {
            // Count transactions in last hour
            $sql = "SELECT COUNT(*) as count FROM transactions WHERE (sender_id = ? OR receiver_id = ?) AND created_at > ?";
            $hourCount = Database::fetch($sql, [$userId, $userId, $oneHourAgo]);
            
            // Count transactions in last 5 minutes
            $sql = "SELECT COUNT(*) as count FROM transactions WHERE (sender_id = ? OR receiver_id = ?) AND created_at > ?";
            $fiveMinCount = Database::fetch($sql, [$userId, $userId, $fiveMinAgo]);
            
            // Today's total
            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE sender_id = ? AND created_at > ? AND type = 'send'";
            $todayTotal = Database::fetch($sql, [$userId, $todayStart]);
            
            // Average transaction amount
            $sql = "SELECT COALESCE(AVG(amount), 0) as avg FROM transactions WHERE sender_id = ? AND type = 'send'";
            $avgAmount = Database::fetch($sql, [$userId]);
            
            return [
                'last_hour_count' => $hourCount['count'] ?? 0,
                'last_5min_count' => $fiveMinCount['count'] ?? 0,
                'today_total' => $todayTotal['total'] ?? 0,
                'avg_amount' => $avgAmount['avg'] ?? 0,
                'account_age_hours' => $accountCreated ? (time() - strtotime($accountCreated)) / 3600 : 0
            ];
        } catch (Exception $e) {
            Logger::error('Failed to get transaction stats', ['error' => $e->getMessage()]);
            return [
                'last_hour_count' => 0,
                'last_5min_count' => 0,
                'today_total' => 0,
                'avg_amount' => 0,
                'account_age_hours' => 0
            ];
        }
    }
    
    /**
     * Check if current time is unusual for transactions
     */
    private static function isUnusualTime(): bool
    {
        $hour = (int) date('H');
        // Transactions between 2 AM and 5 AM are unusual
        return $hour >= 2 && $hour <= 5;
    }
    
    /**
     * Check if this is a new recipient for the user
     */
    private static function isNewRecipient(int $userId, int $recipientId): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM transactions WHERE sender_id = ? AND receiver_id = ?";
            $result = Database::fetch($sql, [$userId, $recipientId]);
            return ($result['count'] ?? 0) == 0;
        } catch (Exception $e) {
            return true;
        }
    }
    
    /**
     * Check if account is new (< 24 hours old)
     */
    private static function isNewAccount(int $userId): bool
    {
        $created = self::getAccountCreationTime($userId);
        if (!$created) return false;
        return (time() - strtotime($created)) < 86400; // 24 hours
    }
    
    /**
     * Get account creation time
     */
    private static function getAccountCreationTime(int $userId): ?string
    {
        try {
            $sql = "SELECT created_at FROM users WHERE id = ?";
            $result = Database::fetch($sql, [$userId]);
            return $result['created_at'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if IP is blacklisted
     */
    private static function isBlacklistedIP(string $ip): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM blacklisted_ips WHERE ip_address = ? AND (expires_at IS NULL OR expires_at > NOW())";
            $result = Database::fetch($sql, [$ip]);
            return ($result['count'] ?? 0) > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get recommendation based on risk level
     */
    private static function getRecommendation(string $riskLevel, int $riskScore): string
    {
        return match (true) {
            $riskScore >= 70 => 'BLOCK - High fraud risk',
            $riskScore >= 50 => 'REVIEW - Manual review required',
            $riskScore >= 30 => 'VERIFY - Additional verification needed',
            default => 'APPROVE - Low risk'
        };
    }
    
    /**
     * Log fraud assessment
     */
    private static function logAssessment(int $userId, float $amount, string $riskLevel, int $riskScore, array $reasons, array $context): void
    {
        try {
            $ip = $context['ip'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $sql = "INSERT INTO fraud_assessments (user_id, amount, risk_level, risk_score, reasons, ip_address, assessed_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            Database::query($sql, [$userId, $amount, $riskLevel, $riskScore, json_encode($reasons), $ip]);
        } catch (Exception $e) {
            // Log but don't fail transaction
            Logger::warning('Failed to log fraud assessment', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Check daily transaction limits
     */
    public static function checkDailyLimit(int $userId, float $amount): array
    {
        $maxDaily = Config::getInt('TRANSACTION_DAILY_LIMIT', 50000);
        $maxSingle = Config::getInt('TRANSACTION_SINGLE_LIMIT', 25000);
        
        $todayTotal = self::getDailyTotal($userId);
        
        if ($amount > $maxSingle) {
            return [
                'allowed' => false,
                'reason' => "Amount exceeds single transaction limit of NPR {$maxSingle}"
            ];
        }
        
        if ($todayTotal + $amount > $maxDaily) {
            return [
                'allowed' => false,
                'reason' => "Daily limit exceeded. Used: {$todayTotal}, limit: {$maxDaily}, requested: {$amount}"
            ];
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Get today's total transactions for user
     */
    private static function getDailyTotal(int $userId): float
    {
        try {
            $todayStart = date('Y-m-d 00:00:00');
            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE sender_id = ? AND created_at > ? AND type = 'send'";
            $result = Database::fetch($sql, [$userId, $todayStart]);
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}
