<?php 
$content = ob_start();
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Linked Banks</h4>
            <a href="<?php echo APP_URL; ?>/index.php?page=add_bank" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Bank</a>
        </div>
        
        <?php if (empty($banks)): ?>
            <div class="card text-center py-5">
                <i class="fas fa-university fa-3x text-muted mb-3"></i>
                <h5>No banks linked</h5>
                <p class="text-muted">Link a bank account to transfer money</p>
                <a href="<?php echo APP_URL; ?>/index.php?page=add_bank" class="btn btn-primary">Add Bank</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($banks as $bank): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo escape($bank['bank_name']); ?></h6>
                                        <p class="mb-0 text-muted small">Account: <?php echo escape($bank['account_number']); ?></p>
                                        <p class="mb-0 text-muted small"><?php echo escape($bank['account_holder_name']); ?></p>
                                    </div>
                                    <?php if ($bank['is_default']): ?>
                                        <span class="badge bg-primary">Default</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-0">
                                <?php if (!$bank['is_default']): ?>
                                    <a href="<?php echo APP_URL; ?>/index.php?page=set_default_bank&id=<?php echo $bank['id']; ?>" class="btn btn-sm btn-outline-primary">Set Default</a>
                                <?php endif; ?>
                                <a href="<?php echo APP_URL; ?>/index.php?page=remove_bank&id=<?php echo $bank['id']; ?>" class="btn btn-sm btn-outline-danger">Remove</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-4">
                <a href="<?php echo APP_URL; ?>/index.php?page=withdraw" class="btn btn-success"><i class="fas fa-arrow-down me-2"></i>Withdraw to Bank</a>
                <a href="<?php echo APP_URL; ?>/index.php?page=load_money" class="btn btn-primary"><i class="fas fa-arrow-up me-2"></i>Load from Bank</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean();

require_once __DIR__ . '/../layouts/main.php';
?>