<?php 
$content = ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white border-0">
                <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add Money</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo APP_URL; ?>/index.php?page=handle_add_money">
                    <?php echo CSRF::getField(); ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount (NPR)</label>
                        <input type="number" name="amount" class="form-control" placeholder="0.00" required min="100" max="100000" step="0.01">
                        <small class="text-muted">Minimum: NPR 100 | Maximum: NPR 100,000</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Amount</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary flex-fill" onclick="setAmount(500)">500</button>
                            <button type="button" class="btn btn-outline-secondary flex-fill" onclick="setAmount(1000)">1,000</button>
                            <button type="button" class="btn btn-outline-secondary flex-fill" onclick="setAmount(5000)">5,000</button>
                            <button type="button" class="btn btn-outline-secondary flex-fill" onclick="setAmount(10000)">10,000</button>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This is a simulation. No real money will be added.
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100" style="padding:12px;border-radius:10px">
                        <i class="fas fa-plus me-2"></i>Add Money
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function setAmount(amount) {
    document.querySelector('input[name="amount"]').value = amount;
}
</script>

<?php 
$content = ob_get_clean();

require_once __DIR__ . '/../layouts/main.php';
?>