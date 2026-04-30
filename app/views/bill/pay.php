<?php 
$content = ob_start();
?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title"><?php echo htmlspecialchars($bill_info['name'] ?? ''); ?> Payment</h4>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        Bill paid successfully!
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    
                    <div class="mb-3">
                        <label for="account_no" class="form-label">Account Number</label>
                        <input type="text" class="form-control" id="account_no" name="account_no" 
                                placeholder="Enter your account number" required>
                        <small class="text-muted">Format: <?php echo htmlspecialchars($bill_info['example'] ?? ''); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (NPR)</label>
                        <input type="number" class="form-control" id="amount" name="amount" 
                                min="<?php echo $bill_info['min'] ?? 0; ?>" max="<?php echo $bill_info['max'] ?? 0; ?>" 
                                step="0.01" required>
                        <small class="text-muted">Range: NPR <?php echo $bill_info['min'] ?? 0; ?> - <?php echo $bill_info['max'] ?? 0; ?></small>
                    </div>
                    
                    <?php if ($is_pin_required): ?>
                    <div class="mb-3">
                        <label for="transaction_pin" class="form-label">Transaction PIN</label>
                        <input type="password" class="form-control" id="transaction_pin" name="transaction_pin" 
                                placeholder="Enter your transaction PIN" required>
                        <small class="text-muted">For security verification</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            Pay Now
                        </button>
                    </div>
                </form>
                
                <div class="mt-3">
                    <a href="<?php echo APP_URL; ?>/index.php?page=bills" class="btn btn-outline-secondary">
                        Back to Bills
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>