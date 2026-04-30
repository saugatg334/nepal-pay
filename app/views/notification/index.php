<?php 
$content = ob_start();
?>

<div class="row">
    <div class="col-md-12">
        <h4 class="mb-3">Notifications</h4>
        
        <?php if (empty($notifications)): ?>
            <div class="card text-center py-5">
                <i class="fas fa-bell fa-3x text-muted mb-3"></i>
                <h5>No notifications</h5>
                <p class="text-muted">You're all caught up!</p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="list-group-item <?php echo $notif['is_read'] ? '' : 'bg-light'; ?>">
                            <div class="d-flex align-items-start">
                                <div class="rounded-circle <?php echo $notif['type'] == 'transaction' ? 'bg-success' : ($notif['type'] == 'security' ? 'bg-danger' : 'bg-info'); ?> text-white d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;min-width:40px">
                                    <i class="fas <?php echo $notif['type'] == 'transaction' ? 'fa-money-bill' : ($notif['type'] == 'security' ? 'fa-shield-alt' : 'fa-info'); ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1"><?php echo escape($notif['title']); ?></h6>
                                        <small class="text-muted"><?php echo timeAgo($notif['created_at']); ?></small>
                                    </div>
                                    <p class="mb-0 text-muted"><?php echo escape($notif['message']); ?></p>
                                </div>
                                <?php if (!$notif['is_read']): ?>
                                    <span class="badge bg-primary ms-2">New</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean();

require_once __DIR__ . '/../layouts/main.php';
?>