<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/public/assets/css/style.css" rel="stylesheet">
    <?php require_once __DIR__ . '/../../helpers/helpers.php'; ?>
    <style>
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
        .auth-card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,0.3);overflow:hidden;max-width:450px;width:100%}
        .auth-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:30px;text-align:center;color:#fff}
        .auth-body{padding:30px}
    </style>
</head>
<body>
<div class="auth-card">
    <div class="auth-header">
        <i class="fas fa-lock fa-2x"></i>
        <h3 class="mt-2">Reset Password</h3>
        <p class="mb-0">Create a new password</p>
    </div>
    <div class="auth-body">
        <?php if (hasFlash('error')): ?>
            <div class="alert alert-danger"><?php echo escape(getFlash('error')); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo APP_URL; ?>/index.php?page=handle_reset_password">
            <?php echo CSRF::getField(); ?>
            
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required minlength="8">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="password_confirm" class="form-control" placeholder="Re-enter password" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border:none;padding:12px;border-radius:10px">Reset Password</button>
        </form>
        
        <div class="text-center mt-3">
            <p class="text-muted"><a href="<?php echo APP_URL; ?>/index.php?page=login">Back to Login</a></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

