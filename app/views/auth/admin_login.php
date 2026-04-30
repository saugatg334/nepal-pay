<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <?php require_once __DIR__ . '/../../helpers/helpers.php'; ?>
    <?php 
    $site_key = Config::get('RECAPTCHA_SITE_KEY', '');
    if ($site_key) {
        echo ReCAPTCHA::getScript($site_key);
    }
    ?>
    <style>
        body{background:linear-gradient(135deg,#1e3c72 0%,#2a5298 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
        .login-card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,0.3);overflow:hidden;max-width:450px;width:100%}
        .login-header{background:linear-gradient(135deg,#1e3c72 0%,#2a5298 100%);padding:40px;text-align:center;color:#fff}
        .login-header h1{font-size:28px;margin:0;font-weight:700}
        .login-header i{font-size:48px;margin-bottom:10px}
        .login-body{padding:40px}
        .form-control{border-radius:10px;padding:12px 15px;border:2px solid #e0e0e0}
        .form-control:focus{border-color:#1e3c72;box-shadow:none}
        .btn-login{background:linear-gradient(135deg,#1e3c72 0%,#2a5298 100%);border:none;border-radius:10px;padding:12px;color:#fff;font-weight:600;width:100%}
        .btn-login:hover{opacity:0.9;color:#fff}
        #recaptcha_token {display:none;}
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <i class="fas fa-shield-alt"></i>
        <h1>Admin Panel</h1>
        <p class="mb-0"><?php echo APP_NAME; ?></p>
    </div>
    <div class="login-body">
        <?php if (hasFlash('error')): ?>
            <div class="alert alert-danger"><?php echo escape(getFlash('error')); ?></div>
        <?php endif; ?>
        
        <?php if (hasFlash('success')): ?>
            <div class="alert alert-success"><?php echo escape(getFlash('success')); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo APP_URL; ?>/index.php?page=handle_admin_login">
            <?php echo CSRF::getField(); ?>
            <input type="hidden" id="recaptcha_token" name="g-recaptcha-response">
            
            <div class="mb-3">
                <label class="form-label">Admin Phone</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="tel" name="phone" class="form-control" placeholder="9746587923" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-login btn-primary">Admin Login <i class="fas fa-arrow-right ms-2"></i></button>
        </form>
        
        <div class="text-center mt-3">
            <a href="<?php echo APP_URL; ?>/index.php?page=login" class="text-muted">User Login</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
