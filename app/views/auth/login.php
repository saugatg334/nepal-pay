<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/public/assets/css/nepalpay-modern.css" rel="stylesheet">
    <style>
        .np-auth-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        .np-auth-page::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            top: -100px;
            right: -100px;
        }
        .np-auth-page::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            bottom: -50px;
            left: -50px;
        }
        .np-auth-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            background: white;
            min-height: 580px;
            max-width: 1000px;
            width: 100%;
            position: relative;
            z-index: 1;
        }
        .np-auth-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .np-auth-left-content {
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .np-auth-icon {
            font-size: 72px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        .np-auth-left h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .np-auth-left p {
            font-size: 15px;
            opacity: 0.9;
            line-height: 1.6;
        }
        .np-auth-right {
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .np-auth-header {
            margin-bottom: 30px;
        }
        .np-auth-header h3 {
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .np-auth-header p {
            color: #64748b;
            font-size: 14px;
        }
        .np-security-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: #d1fae5;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #166534;
            font-weight: 500;
        }
        .np-trust-seals {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .np-trust-seal {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: rgba(255,255,255,0.7);
        }
        .password-toggle {
            position: relative;
        }
        .password-toggle .np-input {
            padding-right: 45px;
        }
        .password-toggle-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            z-index: 10;
            padding: 4px;
            font-size: 16px;
        }
        .np-social-login {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .np-social-btn {
            padding: 12px;
            border: 1.5px solid #e2e8f0;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #1e293b;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .np-social-btn:hover {
            border-color: #667eea;
            background: #f8fafc;
            transform: translateY(-2px);
        }
        .np-divider {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
        }
        .np-divider-line {
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }
        .np-divider-text {
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
        }
        @media (max-width: 768px) {
            .np-auth-wrapper { grid-template-columns: 1fr; min-height: auto; }
            .np-auth-left { display: none; }
            .np-auth-right { padding: 30px 20px; }
        }
    </style>
</head>
<body class="np-auth-page">
    <div class="np-auth-wrapper">
        <!-- Left Side -->
        <div class="np-auth-left">
            <div class="np-auth-left-content">
                <div class="np-auth-icon"><i class="fas fa-wallet"></i></div>
                <h2>Welcome to NepalPay</h2>
                <p>Fast, secure, and easy digital wallet for all your transactions</p>
                <div class="np-trust-seals">
                    <div class="np-trust-seal"><i class="fas fa-shield-alt"></i> 256-bit SSL</div>
                    <div class="np-trust-seal"><i class="fas fa-check-circle"></i> NRB Registered</div>
                    <div class="np-trust-seal"><i class="fas fa-lock"></i> PCI DSS Ready</div>
                </div>
            </div>
        </div>

        <!-- Right Side -->
        <div class="np-auth-right">
            <div class="np-auth-header">
                <h3>Sign In</h3>
                <p>Sign in to NepalPay — Nepal's Digital Wallet</p>
            </div>

            <div class="np-security-banner">
                <i class="fas fa-shield-alt"></i>
                Bank-level encryption &middot; NRB Compliant &middot; ISO 27001 Ready
            </div>

            <?php if (hasFlash('error')): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2" style="border-radius: 10px; border: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo escape(getFlash('error')); ?>
                </div>
            <?php endif; ?>
            
            <?php if (hasFlash('success')): ?>
                <div class="alert alert-success d-flex align-items-center gap-2" style="border-radius: 10px; border: none;">
                    <i class="fas fa-check-circle"></i>
                    <?php echo escape(getFlash('success')); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo APP_URL; ?>/index.php?page=handle_login" id="loginForm">
                <?php echo CSRF::getField(); ?>
                
                <div class="np-form-group">
                    <label class="np-label">Phone or Email</label>
                    <div class="np-input-group">
                        <span class="np-input-icon-wrapper"><i class="fas fa-phone"></i></span>
                        <input type="text" name="identifier" class="np-input np-input-icon" placeholder="9800000000" required autofocus>
                    </div>
                </div>

                <div class="np-form-group">
                    <label class="np-label">Password</label>
                    <div class="password-toggle">
                        <input type="password" name="password" id="loginPassword" class="np-input" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('loginPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label" for="rememberMe" style="color: #64748b; font-size: 14px;">
                            Remember me
                        </label>
                    </div>
                    <a href="<?php echo APP_URL; ?>/index.php?page=forgot_password" style="font-size: 13px; font-weight: 500;">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="np-btn np-btn-primary np-btn-block np-btn-lg" id="loginBtn">
                    <span>Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="np-divider">
                <div class="np-divider-line"></div>
                <div class="np-divider-text">or</div>
                <div class="np-divider-line"></div>
            </div>

            <div class="np-social-login">
                <button type="button" class="np-social-btn" onclick="alert('Google login coming soon')">
                    <i class="fab fa-google"></i> Google
                </button>
                <button type="button" class="np-social-btn" onclick="alert('Facebook login coming soon')">
                    <i class="fab fa-facebook"></i> Facebook
                </button>
            </div>

            <div class="text-center" style="color: #64748b; font-size: 14px;">
                Don't have an account? 
                <a href="<?php echo APP_URL; ?>/index.php?page=register" style="font-weight: 600;">Create Account</a>
            </div>

            <div class="text-center mt-3">
                <a href="<?php echo APP_URL; ?>/index.php?page=admin_login" class="np-btn np-btn-secondary np-btn-sm np-btn-block">
                    <i class="fas fa-shield-alt me-1"></i> Admin Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const btn = event.target.closest('button');
        const icon = btn.querySelector('i');
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
    });
    </script>
</body>
</html>
