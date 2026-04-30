<?php 
$content = ob_start();
?>

<!-- Page Header -->
<div class="np-page-header animate-fade-in">
    <h1><?php echo lang('dashboard'); ?></h1>
    <p>Welcome back, <?php echo escape($user['full_name'] ?? 'User'); ?>! Here's your wallet overview.</p>
</div>

<!-- Wallet Balance + KYC -->
<div class="row mb-4">
    <div class="col-lg-8 mb-3 animate-fade-in stagger-1">
        <div class="np-card np-card-gradient">
            <div class="np-card-gradient-content">
                <div class="np-card-label">Available Balance</div>
                <div class="np-card-amount">NPR <?php echo number_format($wallet['balance'] ?? 0, 2); ?></div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.2);">
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                        <i class="fas fa-wallet"></i> Wallet: <?php echo escape($wallet['wallet_number'] ?? '---'); ?>
                    </div>
                    <span style="font-size: 13px; opacity: 0.8;">Last updated just now</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-3 animate-fade-in stagger-2">
        <div class="np-stat-card">
            <div class="np-stat-header">
                <div>
                    <div class="np-stat-label">KYC Status</div>
                    <div class="np-stat-value" style="color: var(--success); font-size: 20px;">
                        <i class="fas fa-check-circle"></i> Verified
                    </div>
                </div>
                <div class="np-stat-icon info">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
            <p style="font-size: 13px; color: #94a3b8; margin: 0;">Full access unlocked</p>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3 animate-fade-in stagger-2">
        <div class="np-stat-card">
            <div class="np-stat-header">
                <div>
                    <div class="np-stat-label">Total Sent</div>
                    <div class="np-stat-value">NPR <?php echo number_format($analytics['total_sent'] ?? 0, 2); ?></div>
                </div>
                <div class="np-stat-icon expense">
                    <i class="fas fa-arrow-up"></i>
                </div>
            </div>
            <div class="np-stat-change down">
                <i class="fas fa-arrow-up"></i> Outgoing
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3 animate-fade-in stagger-3">
        <div class="np-stat-card">
            <div class="np-stat-header">
                <div>
                    <div class="np-stat-label">Total Received</div>
                    <div class="np-stat-value">NPR <?php echo number_format($analytics['total_received'] ?? 0, 2); ?></div>
                </div>
                <div class="np-stat-icon income">
                    <i class="fas fa-arrow-down"></i>
                </div>
            </div>
            <div class="np-stat-change">
                <i class="fas fa-arrow-down"></i> Incoming
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3 animate-fade-in stagger-4">
        <div class="np-stat-card">
            <div class="np-stat-header">
                <div>
                    <div class="np-stat-label">This Month</div>
                    <div class="np-stat-value">NPR <?php echo number_format(($analytics['monthly_sent'] ?? 0) + ($analytics['monthly_received'] ?? 0), 2); ?></div>
                </div>
                <div class="np-stat-icon reward">
                    <i class="fas fa-chart-pie"></i>
                </div>
            </div>
            <div class="np-stat-change" style="color: var(--warning);">
                <i class="fas fa-calendar"></i> <?php echo date('F Y'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mb-4 animate-fade-in stagger-3">
    <h5 style="font-weight: 700; margin-bottom: 20px;">Quick Actions</h5>
    <div class="np-quick-actions">
        <a href="<?php echo APP_URL; ?>/index.php?page=send_money" class="np-action-btn">
            <div class="np-action-icon">
                <i class="fas fa-paper-plane"></i>
            </div>
            <span class="np-action-label">Send Money</span>
        </a>
        <a href="<?php echo APP_URL; ?>/index.php?page=add_money" class="np-action-btn">
            <div class="np-action-icon">
                <i class="fas fa-plus"></i>
            </div>
            <span class="np-action-label">Add Money</span>
        </a>
        <a href="<?php echo APP_URL; ?>/index.php?page=bills" class="np-action-btn">
            <div class="np-action-icon">
                <i class="fas fa-receipt"></i>
            </div>
            <span class="np-action-label">Pay Bills</span>
        </a>
        <a href="<?php echo APP_URL; ?>/index.php?page=withdraw" class="np-action-btn">
            <div class="np-action-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <span class="np-action-label">Withdraw</span>
        </a>
        <a href="<?php echo APP_URL; ?>/index.php?page=my_qr" class="np-action-btn">
            <div class="np-action-icon">
                <i class="fas fa-qrcode"></i>
            </div>
            <span class="np-action-label">My QR</span>
        </a>
        <a href="<?php echo APP_URL; ?>/index.php?page=scan_qr" class="np-action-btn">
            <div class="np-action-icon">
                <i class="fas fa-scan"></i>
            </div>
            <span class="np-action-label">Scan QR</span>
        </a>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4 animate-fade-in stagger-4">
    <div class="col-lg-8 mb-3">
        <div class="np-chart-container">
            <h5 style="font-weight: 700; margin-bottom: 20px;">Monthly Activity</h5>
            <canvas id="transactionChart"></canvas>
        </div>
    </div>

    <div class="col-lg-4 mb-3">
        <div class="np-chart-container">
            <h5 style="font-weight: 700; margin-bottom: 20px;">Expense Breakdown</h5>
            <canvas id="expenseChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="animate-fade-in stagger-5">
    <h5 style="font-weight: 700; margin-bottom: 20px;">Recent Transactions</h5>
    <div class="np-table-wrap">
        <table class="np-table">
            <thead>
                <tr>
                    <th>Transaction</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentTransactions)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            No transactions yet
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach (array_slice($recentTransactions, 0, 5) as $txn): ?>
                        <?php 
                            $isSender = ($txn['sender_id'] ?? 0) == Session::get('user_id');
                            $type = $txn['type'] ?? 'transfer';
                            
                            if ($type === 'bill_payment') {
                                $iconClass = 'bill';
                                $icon = 'fa-file-invoice';
                                $title = 'Bill Payment';
                            } elseif ($type === 'add_money') {
                                $iconClass = 'add';
                                $icon = 'fa-plus';
                                $title = 'Added Money';
                            } elseif ($isSender) {
                                $iconClass = 'sent';
                                $icon = 'fa-arrow-up';
                                $title = 'Sent to ' . escape($txn['receiver_name'] ?? 'User');
                            } else {
                                $iconClass = 'received';
                                $icon = 'fa-arrow-down';
                                $title = 'Received from ' . escape($txn['sender_name'] ?? 'User');
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="np-txn-type">
                                    <div class="np-txn-icon <?php echo $iconClass; ?>">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo $title; ?></strong>
                                        <br><small style="color: #94a3b8;"><?php echo escape($txn['description'] ?? ''); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo timeAgo($txn['created_at'] ?? ''); ?></td>
                            <td class="<?php echo $isSender ? 'text-danger' : 'text-success'; ?> fw-bold">
                                <?php echo $isSender ? '-' : '+'; ?>NPR <?php echo number_format($txn['amount'] ?? 0, 2); ?>
                            </td>
                            <td>
                                <span class="np-badge np-badge-success">Completed</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="text-center mt-3">
        <a href="<?php echo APP_URL; ?>/index.php?page=transactions" class="np-btn np-btn-secondary np-btn-sm">
            View All Transactions
        </a>
    </div>
</div>

<script>
// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Activity Chart
    const txnCtx = document.getElementById('transactionChart');
    if (txnCtx) {
        new Chart(txnCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sent',
                    data: [15000, 22000, 18000, 25000, 20000, <?php echo $analytics['monthly_sent'] ?? 0; ?>],
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }, {
                    label: 'Received',
                    data: [20000, 28000, 25000, 30000, 35000, <?php echo $analytics['monthly_received'] ?? 0; ?>],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, font: { size: 12 } }
                    }
                },
                scales: {
                    y: {
                        grid: { color: '#e2e8f0' },
                        ticks: {
                            callback: function(value) {
                                return 'NPR ' + (value / 1000) + 'K';
                            }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // Expense Breakdown Chart
    const expenseCtx = document.getElementById('expenseChart');
    if (expenseCtx) {
        new Chart(expenseCtx, {
            type: 'doughnut',
            data: {
                labels: ['Transfers', 'Bills', 'Shopping', 'Others'],
                datasets: [{
                    data: [40, 30, 20, 10],
                    backgroundColor: ['#667eea', '#764ba2', '#06b6d4', '#10b981'],
                    borderColor: '#fff',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, font: { size: 12 } }
                    }
                }
            }
        });
    }
});
</script>

<?php 
$content = ob_get_clean();

require_once __DIR__ . '/../layouts/main.php';
?>
