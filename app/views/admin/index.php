<?php 
$content = ob_start();
?>

<div class="row">
    <div class="col-md-12">
        <h4 class="mb-3">Admin Dashboard</h4>
        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h3><?php echo $total_users; ?></h3>
                        <p class="mb-0 text-muted">Total Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-snowflake fa-2x text-danger mb-2"></i>
                        <h3><?php echo $frozen_users; ?></h3>
                        <p class="mb-0 text-muted">Frozen Accounts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-wallet fa-2x text-success mb-2"></i>
                        <h3>NPR <?php echo number_format($total_balance, 0); ?></h3>
                        <p class="mb-0 text-muted">Total Balance</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-exchange-alt fa-2x text-info mb-2"></i>
                        <h3><?php echo count($daily_stats); ?></h3>
                        <p class="mb-0 text-muted">Today's Transactions</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Recent Users</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users['data'] as $user): ?>
                                <tr>
                                    <td><?php echo escape($user['full_name']); ?></td>
                                    <td><?php echo escape($user['phone']); ?></td>
                                    <td><?php echo escape($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['is_frozen']): ?>
                                            <span class="badge bg-danger">Frozen</span>
                                        <?php elseif ($user['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="<?php echo APP_URL; ?>/index.php?page=admin_user_detail&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        <?php if ($user['is_frozen']): ?>
                                            <a href="<?php echo APP_URL; ?>/index.php?page=unfreeze_user&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-success">Unfreeze</a>
                                        <?php else: ?>
                                            <a href="<?php echo APP_URL; ?>/index.php?page=freeze_user&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger">Freeze</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="<?php echo APP_URL; ?>/index.php?page=admin_users" class="btn btn-primary">View All Users</a>
            <a href="<?php echo APP_URL; ?>/index.php?page=admin_transactions" class="btn btn-outline-primary">View All Transactions</a>
            <a href="<?php echo APP_URL; ?>/index.php?page=admin_analytics" class="btn btn-outline-primary">Analytics</a>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();

require_once __DIR__ . '/../layouts/main.php';
?>