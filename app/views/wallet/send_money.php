<?php 
$content = ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white border-0">
                <h4 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Send Money</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo APP_URL; ?>/index.php?page=handle_send_money">
                    <?php echo CSRF::getField(); ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Recipient (Phone/Email)</label>
                        <input type="text" name="receiver" id="receiver" class="form-control" placeholder="9800000000 or email@example.com" required>
                        <div id="receiver-info" class="mt-2 small"></div>
                    </div>
                    
                    <?php if (!empty($beneficiaries)): ?>
                        <div class="mb-3">
                            <label class="form-label">Quick Select</label>
                            <div class="d-flex flex-wrap gap-2">
                                 <?php foreach ($beneficiaries as $b): ?>
                                     <button type="button" class="btn btn-outline-secondary btn-sm" 
                                         onclick="selectBeneficiary('<?php echo escape($b['phone']); ?>', <?php echo json_encode($b['full_name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)">
                                         <?php echo escape($b['nickname'] ?: $b['full_name']); ?>
                                     </button>
                                 <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                     <div class="mb-3">
                         <label class="form-label">Amount (NPR)</label>
                         <input type="number" name="amount" id="amount" class="form-control" placeholder="0.00" required min="10" step="0.01">
                     </div>
                     
                     <div class="mb-3">
                         <label class="form-label">Transaction PIN <small class="text-muted">(required for security)</small></label>
                         <input type="password" name="transaction_pin" id="transaction_pin" class="form-control" placeholder="Enter your PIN" maxlength="6" pattern="\d{4,6}" required>
                         <div class="form-text">4-6 digit PIN set in your account settings</div>
                     </div>
                     
                     <div class="mb-3">
                         <label class="form-label">Note (Optional)</label>
                        <textarea name="note" class="form-control" placeholder="What's this for?" rows="2"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Minimum: NPR 10 | Maximum: NPR 100,000 per transaction
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100" style="padding:12px;border-radius:10px">
                        <i class="fas fa-paper-plane me-2"></i>Send Money
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

 <script>
function selectBeneficiary(phone, name) {
    document.getElementById('receiver').value = phone;
    var info = document.getElementById('receiver-info');
    // Clear previous content
    info.innerHTML = '';
    // Create icon
    var icon = document.createElement('i');
    icon.className = 'fas fa-check-circle';
    // Create text node for name (safe from XSS)
    var text = document.createTextNode(' ' + name);
    // Create wrapper span
    var wrapper = document.createElement('span');
    wrapper.className = 'text-success';
    wrapper.appendChild(icon);
    wrapper.appendChild(text);
    info.appendChild(wrapper);
}

document.getElementById('receiver').addEventListener('blur', function() {
    var identifier = this.value;
    if (identifier.length >= 3) {
        fetch('<?php echo APP_URL; ?>/index.php?page=search_user&q=' + encodeURIComponent(identifier))
            .then(response => response.json())
            .then(data => {
                var info = document.getElementById('receiver-info');
                if (data.success && data.user) {
                    // Safe DOM manipulation
                    info.innerHTML = '';
                    var icon = document.createElement('i');
                    icon.className = 'fas fa-check-circle';
                    var text = document.createTextNode(' ' + data.user.full_name);
                    var wrapper = document.createElement('span');
                    wrapper.className = 'text-success';
                    wrapper.appendChild(icon);
                    wrapper.appendChild(text);
                    info.appendChild(wrapper);
                } else {
                    info.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> User not found</span>';
                }
            })
            .catch(err => {
                console.error('Search error:', err);
            });
    }
});
</script>

<?php 
$content = ob_get_clean();

require_once __DIR__ . '/../layouts/main.php';
?>