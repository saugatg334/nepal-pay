<?php
declare(strict_types=1);

namespace NepalPay\Core;

/**
 * Security Headers Middleware
 * 
 * WHY: Apache mod_headers may not be available on all hosts.
 * This PHP fallback ensures security headers are always emitted.
 * 
 * Call SecurityHeaders::apply() at the start of every request
 * (already wired into public/index.php).
 */
class SecurityHeaders
{
    /**
     * Apply all security headers
     */
    public static function apply(): void
    {
        // Prevent headers from being sent multiple times
        if (headers_sent()) {
            return;
        }
        
        // Strict-Transport-Security (HSTS)
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        
        // Content-Security-Policy
        $csp = "default-src 'self'; "
             . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; "
             . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
             . "font-src 'self' https://fonts.gstatic.com; "
             . "img-src 'self' data: https:; "
             . "connect-src 'self'; "
             . "frame-ancestors 'none'; "
             . "base-uri 'self'; "
             . "form-action 'self'; "
             . "upgrade-insecure-requests;";
        header('Content-Security-Policy: ' . $csp);
        
        // X-Frame-Options
        header('X-Frame-Options: DENY');
        
        // X-Content-Type-Options
        header('X-Content-Type-Options: nosniff');
        
        // X-XSS-Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer-Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions-Policy
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(self), payment=()');
        
        // Remove X-Powered-By (PHP)
        header_remove('X-Powered-By');
        
        // Cache-Control for sensitive pages
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    /**
     * Report CSP violations to database
     */
    public static function reportCspViolation(): void
    {
        $report = file_get_contents('php://input');
        
        if (empty($report)) {
            return;
        }
        
        $data = json_decode($report, true);
        
        if (empty($data) || !isset($data['csp-report'])) {
            return;
        }
        
        $cspReport = $data['csp-report'];
        
        try {
            $sql = "INSERT INTO csp_violations 
                    (document_uri, referrer, blocked_uri, violated_directive, original_policy, source_file, line_number, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            \Database::query($sql, [
                $cspReport['document-uri'] ?? '',
                $cspReport['referrer'] ?? '',
                $cspReport['blocked-uri'] ?? '',
                $cspReport['violated-directive'] ?? '',
                $cspReport['original-policy'] ?? '',
                $cspReport['source-file'] ?? '',
                $cspReport['line-number'] ?? 0
            ]);
            
            Logger::warning('CSP violation reported', [
                'document' => $cspReport['document-uri'] ?? '',
                'blocked' => $cspReport['blocked-uri'] ?? '',
                'directive' => $cspReport['violated-directive'] ?? ''
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to log CSP violation', ['error' => $e->getMessage()]);
        }
    }
}
