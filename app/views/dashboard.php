<?php
/**
 * Dashboard View
 */
?>

<div class="dashboard-header mb-4">
    <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
    <p class="text-muted">Welcome back, <?php echo htmlspecialchars($userName ?? 'User') ?>!</p>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Wallet Balance</p>
                        <h3 class="mb-0">NPR <?php echo number_format($walletBalance ?? 0, 2) ?></h3>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-wallet fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Money Sent</p>
                        <h3 class="mb-0">NPR <?php echo number_format($stats['total_sent'] ?? 0, 2) ?></h3>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-arrow-up fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Money Received</p>
                        <h3 class="mb-0">NPR <?php echo number_format($stats['total_received'] ?? 0, 2) ?></h3>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-arrow-down fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Bills Paid</p>
                        <h3 class="mb-0">NPR <?php echo number_format($stats['total_bills'] ?? 0, 2) ?></h3>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-file-invoice-dollar fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts & Recent Transactions -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Transaction History</h5>
            </div>
            <div class="card-body">
                <canvas id="transactionChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Transaction Breakdown</h5>
            </div>
            <div class="card-body">
                <canvas id="expenseChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions Table -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Transactions</h5>
                <a href="/wallet/public/?page=transactions" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions ?? [] as $txn): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($txn['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    $icon = '';
                                    if ($txn['type'] === 'send') $icon = '<i class="fas fa-arrow-up text-danger"></i>';
                                    elseif ($txn['type'] === 'receive') $icon = '<i class="fas fa-arrow-down text-success"></i>';
                                    elseif ($txn['type'] === 'bill_payment') $icon = '<i class="fas fa-file-invoice-dollar text-warning"></i>';
                                    ?>
                                    <?php echo $icon; ?> <?php echo htmlspecialchars($txn['description'] ?? 'Transaction'); ?>
                                </td>
                                <td><?php echo ucfirst($txn['type']); ?></td>
                                <td>NPR <?php echo number_format($txn['amount'], 2); ?></td>
                                <td>
                                    <?php 
                                    $badgeClass = 'badge-success';
                                    if ($txn['status'] === 'pending') $badgeClass = 'badge-warning';
                                    elseif ($txn['status'] === 'failed') $badgeClass = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($txn['status']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize charts with real data
    document.addEventListener('DOMContentLoaded', function() {
        // Transaction Line Chart
        const transactionCtx = document.getElementById('transactionChart').getContext('2d');
        new Chart(transactionCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyData ?? [], 'date')); ?>,
                datasets: [
                    {
                        label: 'Sent',
                        data: <?php echo json_encode(array_column($monthlyData ?? [], 'sent')); ?>,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Received',
                        data: <?php echo json_encode(array_column($monthlyData ?? [], 'received')); ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });

        // Expense Pie Chart
        const expenseCtx = document.getElementById('expenseChart').getContext('2d');
        new Chart(expenseCtx, {
            type: 'doughnut',
            data: {
                labels: ['Sent', 'Received', 'Bills'],
                datasets: [{
                    data: [
                        <?php echo $stats['total_sent'] ?? 0; ?>,
                        <?php echo $stats['total_received'] ?? 0; ?>,
                        <?php echo $stats['total_bills'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        '#ef4444',
                        '#10b981',
                        '#f59e0b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>

<style>
    .stat-card {
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .stat-card .card-body {
        padding: 25px;
    }

    .table tbody tr:hover {
        background-color: var(--light-bg);
    }
</style>
