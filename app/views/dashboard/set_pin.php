<?php 
$content = ob_start();
$user = $user ?? [];
?>

<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white border-0">
                <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Transaction PIN</h4>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    Set a 4-6 digit PIN to authorize money transfers, bill payments, and withdrawals. 
                    This adds an extra layer of security to your transactions.
                </p>
                
                <?php if (hasFlash('error')): ?>
                    <div class="alert alert-danger"><?php echo escape(getFlash('error')); ?></div>
                <?php endif; ?>
                <?php if (hasFlash('success')): ?>
                    <div class="alert alert-success"><?php echo escape(getFlash('success')); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo APP_URL; ?>/index.php?page=handle_set_pin">
                    <?php echo CSRF::getField(); ?>
                    
                    <div class="mb-3">
                        <label class="form-label">New PIN</label>
                        <input type="password" name="pin" class="form-control" placeholder="4-6 digit PIN" maxlength="6" pattern="\d{4,6}" required autocomplete="one-time-code">
                        <div class="form-text">4-6 numeric digits</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirm PIN</label>
                        <input type="password" name="pin_confirm" class="form-control" placeholder="Confirm PIN" maxlength="6" pattern="\d{4,6}" required autocomplete="one-time-code">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Set PIN
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
