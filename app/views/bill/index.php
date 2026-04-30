<?php 
$content = ob_start();
?>

<div class="row">
    <div class="col-md-12">
        <h4 class="mb-3">Bill Payments</h4>
        
        <div class="row">
            <?php foreach ($bill_types as $key => $bill): ?>
                <div class="col-md-3 mb-3">
                    <a href="<?php echo APP_URL; ?>/index.php?page=pay_bill&type=<?php echo $key; ?>" class="text-decoration-none">
                        <div class="card h-100 text-center" style="border-radius:15px;transition:transform 0.2s">
                            <div class="card-body">
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px">
                                    <i class="fas fa-<?php echo $key == 'ntc' || $key == 'ncell' ? 'mobile' : ($key == 'nea' ? 'bolt' : 'wifi'); ?> fa-lg"></i>
                                </div>
                                <h6><?php echo $bill['name']; ?></h6>
                                <p class="mb-0 text-muted small">NPR <?php echo $bill['min']; ?> - <?php echo $bill['max']; ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-4">
            <a href="<?php echo APP_URL; ?>/index.php?page=bill_history" class="btn btn-outline-primary">
                <i class="fas fa-history me-2"></i>View History
            </a>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();

require_once __DIR__ . '/../layouts/main.php';
?>