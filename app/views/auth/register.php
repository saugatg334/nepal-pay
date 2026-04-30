<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/public/assets/css/style.css" rel="stylesheet">
    <?php require_once __DIR__ . '/../../helpers/helpers.php'; ?>
    <style>
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
        .register-card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,0.3);overflow:hidden;max-width:500px;width:100%}
        .register-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:30px;text-align:center;color:#fff}
        .register-body{padding:30px}
    </style>
</head>
<body>
<div class="register-card">
    <div class="register-header">
        <i class="fas fa-wallet fa-2x"></i>
        <h3 class="mt-2">Create Account</h3>
        <p class="mb-0">Join <?php echo APP_NAME; ?> today</p>
    </div>
    <div class="register-body">
        <?php if (hasFlash('error')): ?>
            <div class="alert alert-danger"><?php echo escape(getFlash('error')); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo APP_URL; ?>/index.php?page=handle_register">
            <?php echo CSRF::getField(); ?>
            
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" placeholder="Your full name" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-control" placeholder="9800000000" required>
                <small class="text-muted">Start with 98</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required minlength="8">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirm" class="form-control" placeholder="Re-enter password" required>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="terms" required>
                <label class="form-check-label" for="terms">I agree to the <a href="#">Terms & Conditions</a></label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border:none;padding:12px;border-radius:10px">Register</button>
        </form>
        
        <div class="text-center mt-3">
            <p class="text-muted">Already have an account? <a href="<?php echo APP_URL; ?>/index.php?page=login">Login</a></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>