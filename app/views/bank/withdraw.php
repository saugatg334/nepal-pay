<?php 
$content = ob_start();
?>

<!-- Page Header -->
<div class="np-page-header animate-fade-in">
    <h1>Withdraw Money</h1>
    <p>Transfer money from your wallet to your bank account</p>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="np-card">
            <div class="np-card-header">
                <h5 class="mb-0">Withdraw to Bank Account</h5>
            </div>
            <div class="np-card-body">
                <form method="POST" action="<?php echo APP_URL; ?>/index.php?page=handle_withdraw">
                    <?php echo CSRF::getField(); ?>
                    
                    <div class="np-form-group">
                        <label class="np-label">Select Bank Account</label>
                        <select name="bank_account_id" class="np-input np-select" required>
                            <option value="">Choose bank account</option>
                            <?php foreach ($bankAccounts ?? [] as $bank): ?>
                                <option value="<?php echo $bank['id']; ?>">
                                    <?php echo escape($bank['bank_name']); ?> - <?php echo escape($bank['account_number']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="np-form-group">
                        <label class="np-label">Amount (NPR)</label>
                        <input type="number" name="amount" id="amount" class="np-input" placeholder="Enter amount" min="100" required>
                        <small class="text-muted">Minimum: NPR 100 | Fee: NPR 10</small>
                    </div>
                    
                    <div class="np-form-group">
                        <label class="np-label">Transaction PIN</label>
                        <input type="password" name="pin" class="np-input" placeholder="Enter 4-6 digit PIN" maxlength="6" required>
                    </div>
                    
                    <button type="submit" class="np-btn np-btn-primary np-btn-block np-btn-lg">
                        <i class="fas fa-arrow-down"></i> Withdraw
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="np-card">
            <div class="np-card-header">
                <h5 class="mb-0">Your Bank Accounts</h5>
            </div>
            <div class="np-card-body">
                <?php if (empty($bankAccounts)): ?>
                    <p class="text-muted">No bank accounts linked</p>
                    <a href="<?php echo APP_URL; ?>/index.php?page=add_bank" class="np-btn np-btn-secondary np-btn-sm">
                        <i class="fas fa-plus"></i> Add Bank
                    </a>
                <?php else: ?>
                    <?php foreach ($bankAccounts ?? [] as $bank): ?>
                        <div class="np-stat-card mb-3">
                            <div class="np-stat-header">
                                <div>
                                    <div class="np-stat-label"><?php echo escape($bank['bank_name']); ?></div>
                                    <div class="np-stat-value" style="font-size: 16px;"><?php echo escape($bank['account_number']); ?></div>
                                </div>
                                <div class="np-stat-icon info">
                                    <i class="fas fa-university"></i>
                                </div>
                            </div>
                            <?php if (!empty($bank['is_default'])): ?>
                                <span class="np-badge np-badge-primary">Default</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="np-card mt-4">
            <div class="np-card-header">
                <h5 class="mb-0">Withdraw Info</h5>
            </div>
            <div class="np-card-body">
                <ul class="list-unstyled" style="font-size: 14px;">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Instant transfer to major banks</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> NPR 10 flat fee</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Processing: 1-24 hours</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();

require_once __DIR__ . '/../layouts/main.php';
?>
