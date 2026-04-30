<?php 
$content = ob_start();
?>

<!-- Page Header -->
<div class="np-page-header animate-fade-in">
    <h1><?php echo lang('profile'); ?></h1>
    <p>Manage your account settings and profile information</p>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="np-card">
            <div class="np-card-body text-center">
                <div class="np-sidebar-avatar" style="width: 80px; height: 80px; font-size: 32px; margin: 0 auto 15px;">
                    <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
                </div>
                <h4><?php echo escape($user['full_name'] ?? 'User'); ?></h4>
                <p class="text-muted"><?php echo escape($user['email'] ?? ''); ?></p>
                <span class="np-badge np-badge-success">Verified</span>
            </div>
        </div>
    </div>

    <div class="col-lg-8 mb-4">
        <div class="np-card">
            <div class="np-card-header">
                <h5 class="mb-0">Profile Information</h5>
            </div>
            <div class="np-card-body">
                <form method="POST" action="<?php echo APP_URL; ?>/index.php?page=update_profile">
                    <?php echo CSRF::getField(); ?>
                    
                    <div class="np-form-group">
                        <label class="np-label">Full Name</label>
                        <input type="text" name="full_name" class="np-input" value="<?php echo escape($user['full_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="np-form-group">
                        <label class="np-label">Email</label>
                        <input type="email" class="np-input" value="<?php echo escape($user['email'] ?? ''); ?>" disabled>
                        <small class="text-muted">Email cannot be changed</small>
                    </div>
                    
                    <div class="np-form-group">
                        <label class="np-label">Phone</label>
                        <input type="tel" class="np-input" value="<?php echo escape($user['phone'] ?? ''); ?>" disabled>
                        <small class="text-muted">Phone cannot be changed</small>
                    </div>
                    
                    <button type="submit" class="np-btn np-btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Security Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="np-card">
            <div class="np-card-header">
                <h5 class="mb-0">Security</h5>
            </div>
            <div class="np-card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="np-stat-card">
                            <div class="np-stat-header">
                                <div>
                                    <div class="np-stat-label">Password</div>
                                    <div class="np-stat-value" style="font-size: 16px;">••••••••</div>
                                </div>
                                <div class="np-stat-icon info">
                                    <i class="fas fa-lock"></i>
                                </div>
                            </div>
                            <a href="<?php echo APP_URL; ?>/index.php?page=forgot_password" class="np-btn np-btn-secondary np-btn-sm mt-2">
                                Change Password
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="np-stat-card">
                            <div class="np-stat-header">
                                <div>
                                    <div class="np-stat-label">Transaction PIN</div>
                                    <div class="np-stat-value" style="font-size: 16px;">***•</div>
                                </div>
                                <div class="np-stat-icon reward">
                                    <i class="fas fa-key"></i>
                                </div>
                            </div>
                            <a href="<?php echo APP_URL; ?>/index.php?page=set_pin" class="np-btn np-btn-secondary np-btn-sm mt-2">
                                Set PIN
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();

require_once __DIR__ . '/../layouts/main.php';
?>
