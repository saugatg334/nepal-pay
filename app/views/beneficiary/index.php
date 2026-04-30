<?php 
$content = ob_start();
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Beneficiaries</h4>
            <a href="<?php echo APP_URL; ?>/index.php?page=add_beneficiary" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add</a>
        </div>
        
        <?php if (empty($beneficiaries)): ?>
            <div class="card text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5>No beneficiaries yet</h5>
                <p class="text-muted">Add frequently used contacts for quick transfers</p>
                <a href="<?php echo APP_URL; ?>/index.php?page=add_beneficiary" class="btn btn-primary">Add Beneficiary</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($beneficiaries as $b): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px">
                                        <?php echo strtoupper(substr($b['full_name'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo escape($b['nickname'] ?: $b['full_name']); ?></h6>
                                        <p class="mb-0 text-muted small"><?php echo escape($b['phone']); ?></p>
                                    </div>
                                    <?php if ($b['is_favorite']): ?>
                                        <i class="fas fa-star text-warning"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-0">
                                <?php if ($b['bank_name']): ?>
                                    <div class="mb-2 small text-muted">
                                        <i class="fas fa-university me-1"></i><?php echo escape($b['bank_name']); ?> ****<?php echo substr($b['account_number'], -4); ?> (Default)
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-warning toggle-fav" data-id="<?php echo $b['id']; ?>" data-fav="<?php echo $b['is_favorite'] ? 1 : 0; ?>">
                                        <i class="fas fa-star<?php echo $b['is_favorite'] ? '' : '-o'; ?>"></i>
                                    </button>
                                    <a href="<?php echo APP_URL; ?>/index.php?page=send_money" class="btn btn-sm btn-primary flex-grow-1" onclick="document.getElementById('receiver').value='<?php echo escape($b['phone']); ?>'; return false;">Quick Send</a>
                                    <a href="<?php echo APP_URL; ?>/index.php?page=remove_beneficiary&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-danger">×</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean();

require_once __DIR__ . '/../layouts/main.php';
?>