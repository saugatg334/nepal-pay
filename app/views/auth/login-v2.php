<?php
/**
 * NepalPay v2.0 — Premium Login Page
 * 
 * Features:
 * - Glassmorphism card with gradient background
 * - Trust badges (bank-level encryption, NRB compliant)
 * - Biometric login future-ready section
 * - Quick signup link
 * - Forgot password
 * - Smooth animations
 */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NepalPay — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/nepalpay-v2.css">
    <style>
        .np-auth-bg {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(79, 70, 229, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(16, 185, 129, 0.06) 0%, transparent 50%),
                linear-gradient(135deg, #f8fafc 0%, #eef2ff 50%, #ecfdf5 100%);
        }
        
        .np-auth-bg::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.06) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(5deg); }
            66% { transform: translate(-20px, 20px) rotate(-5deg); }
        }
        
        .np-glass-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 
                0 24px 48px -12px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        }
        
        .np-input-icon {
            position: relative;
        }
        
        .np-input-icon svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--np-text-muted);
            width: 20px;
            height: 20px;
            pointer-events: none;
        }
        
        .np-input-icon input {
            padding-left: 48px;
        }
        
        .np-biometric-section {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px dashed var(--np-border);
            text-align: center;
        }
        
        .np-biometric-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--np-bg);
            border: 1.5px solid var(--np-border);
            border-radius: var(--np-radius);
            color: var(--np-text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--np-transition);
        }
        
        .np-biometric-btn:hover {
            border-color: var(--np-primary);
            color: var(--np-primary);
        }
        
        .np-trust-seal {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: var(--np-text-muted);
        }
        
        .np-trust-seal svg {
            width: 16px;
            height: 16px;
            color: var(--np-success);
        }
        
        .np-security-banner {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: var(--np-success-bg);
            border: 1px solid #bbf7d0;
            border-radius: var(--np-radius);
            margin-bottom: 24px;
            font-size: 0.8125rem;
            color: #166534;
            font-weight: 500;
        }
        
        .np-security-banner svg {
            width: 20px;
            height: 20px;
            color: var(--np-success);
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="np-auth-bg"></div>
    
    <div class="np-auth-page">
        <div class="np-auth-container">
            <!-- Glass Card -->
            <div class="np-glass-card np-auth-card">
                
                <!-- Logo -->
                <div class="np-auth-logo">
                    <div class="np-auth-logo-icon">NP</div>
                    <h1>Welcome back</h1>
                    <p>Sign in to NepalPay — Nepal's Digital Wallet</p>
                </div>
                
                <!-- Security Banner -->
                <div class="np-security-banner">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Bank-level encryption &middot; NRB Compliant &middot; ISO 27001 Ready
                </div>
                
                <!-- Flash Messages -->
                <?php if (hasFlash('error')): ?>
                <div class="np-alert np-alert-danger np-mb-4">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= escape(getFlash('error')) ?>
                </div>
                <?php endif; ?>
                
                <?php if (hasFlash('success')): ?>
                <div class="np-alert np-alert-success np-mb-4">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <?= escape(getFlash('success')) ?>
                </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" action="<?= APP_URL ?>/index.php?page=login&action=handle" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= \NepalPay\Helpers\CSRF::getToken() ?>">
                    
                    <div class="np-form-group">
                        <label class="np-label">Phone or Email</label>
                        <div class="np-input-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <input 
                                type="text" 
                                name="identifier" 
                                class="np-input" 
                                placeholder="Enter phone number or email"
                                required
                                autofocus
                            >
                        </div>
                    
                    <div class="np-form-group">
                        <label class="np-label">Password</label>
                        <div class="np-input-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <input 
                                type="password" 
                                name="password" 
                                class="np-input" 
                                placeholder="Enter your password"
                                required
                            >
                        </div>
                        <div style="text-align: right; margin-top: 8px;">
                            <a href="<?= APP_URL ?>/index.php?page=forgot_password" style="font-size: 0.8125rem; font-weight: 500;">
                                Forgot password?
                            </a>
                        </div>
                    
                    <button type="submit" class="np-btn np-btn-primary np-btn-lg np-btn-block" id="loginBtn">
                        <span>Sign In</span>
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-left: 4px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </button>
                </form>
                
                <!-- Biometric Section -->
                <div class="np-biometric-section">
                    <button type="button" class="np-biometric-btn" onclick="alert('Biometric login coming soon!')">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.131A8 8 0 008 8m0 0a8 8 0 00-8 8c0 2.472.345 4.865.99 7.131M8 8a8 8 0 018 8m0 0a8 8 0 01-8 8"/>
                        </svg>
                        Login with Biometric
                    </button>
                    <p style="font-size: 0.75rem; color: var(--np-text-muted); margin-top: 8px;">
                        Face ID / Fingerprint / PIN
                    </p>
                </div>
                
                <!-- Divider -->
                <div style="display: flex; align-items: center; gap: 16px; margin: 24px 0;">
                    <div style="flex: 1; height: 1px; background: var(--np-border);"></div>
                    <span style="font-size: 0.8125rem; color: var(--np-text-muted);">or</span>
                    <div style="flex: 1; height: 1px; background: var(--np-border);"></div>
                
                <!-- Quick Signup -->
                <div class="np-text-center">
                    <p style="font-size: 0.9375rem; color: var(--np-text-secondary); margin-bottom: 12px;">
                        New to NepalPay?
                    </p>
                    <a href="<?= APP_URL ?>/index.php?page=register" class="np-btn np-btn-secondary np-btn-block">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 4px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Create Account — It's Free
                    </a>
                </div>
            
            <!-- Trust Badges -->
            <div style="display: flex; align-items: center; justify-content: center; gap: 24px; margin-top: 24px; flex-wrap: wrap;">
                <div class="np-trust-seal">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    256-bit SSL
                </div>
                <div class="np-trust-seal">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    NRB Registered
                </div>
                <div class="np-trust-seal">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    PCI DSS Ready
                </div>
            
        </div>
    
    <script>
        // Loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = `
                <svg width="18" height="18" style="animation: npSpin 1s linear infinite;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Signing in...</span>
            `;
        });
    </script>
</body>
</html>
