<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/helpers/debug_logger.php';

// Set page title
$pageTitle = 'Debug Logs Viewer';

// Get logs
$debugLogs = DebugLogger::getLogs(100, 'general');
$paymentLogs = DebugLogger::getLogs(100, 'payment');

// Get requested type from query string
$logType = $_GET['type'] ?? 'payment';
$logsToDisplay = ($logType === 'payment') ? $paymentLogs : $debugLogs;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo $pageTitle; ?> — NepalPay</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #f8f9fa;
      color: #333;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .header {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      border: 1px solid #eee;
    }
    
    .header h1 {
      font-size: 24px;
      margin-bottom: 10px;
    }
    
    .header p {
      color: #666;
      font-size: 14px;
    }
    
    .controls {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    
    .btn {
      padding: 10px 16px;
      border: 1px solid #ddd;
      border-radius: 6px;
      background: #fff;
      cursor: pointer;
      font-size: 14px;
      transition: all 0.2s;
    }
    
    .btn:hover {
      border-color: #0891b2;
      color: #0891b2;
    }
    
    .btn.active {
      background: #0891b2;
      color: white;
      border-color: #0891b2;
    }
    
    .btn.danger {
      background: #dc2626;
      color: white;
      border-color: #dc2626;
    }
    
    .logs-container {
      background: #fff;
      border: 1px solid #eee;
      border-radius: 8px;
      overflow: hidden;
    }
    
    .log-entry {
      padding: 16px;
      border-bottom: 1px solid #eee;
      font-family: 'Monaco', 'Menlo', monospace;
      font-size: 12px;
      line-height: 1.5;
      background: #fafafa;
    }
    
    .log-entry:last-child {
      border-bottom: none;
    }
    
    .log-entry.raw {
      white-space: pre-wrap;
      word-break: break-all;
      overflow-x: auto;
    }
    
    .log-entry.json {
      background: #f5f5f5;
    }
    
    .log-timestamp {
      color: #999;
      font-size: 11px;
    }
    
    .log-level {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 3px;
      font-weight: bold;
      margin-right: 8px;
      font-size: 11px;
    }
    
    .log-level.DEBUG {
      background: #e0e7ff;
      color: #3730a3;
    }
    
    .log-level.INFO {
      background: #cffafe;
      color: #164e63;
    }
    
    .log-level.WARNING {
      background: #fed7aa;
      color: #92400e;
    }
    
    .log-level.ERROR {
      background: #fecaca;
      color: #7f1d1d;
    }
    
    .log-level.SUCCESS {
      background: #bbf7d0;
      color: #065f46;
    }
    
    .empty-state {
      padding: 40px;
      text-align: center;
      color: #999;
    }
    
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .stat-card {
      background: #fff;
      padding: 15px;
      border-radius: 8px;
      border: 1px solid #eee;
      text-align: center;
    }
    
    .stat-value {
      font-size: 24px;
      font-weight: bold;
      color: #0891b2;
    }
    
    .stat-label {
      font-size: 12px;
      color: #666;
      margin-top: 5px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1><?php echo $pageTitle; ?></h1>
      <p>Real-time debugging information for payment flow and button interactions</p>
    </div>
    
    <div class="controls">
      <a href="?type=payment" class="btn <?php echo $logType === 'payment' ? 'active' : ''; ?>">
        Payment Flow Logs
      </a>
      <a href="?type=general" class="btn <?php echo $logType === 'general' ? 'active' : ''; ?>">
        General Debug Logs
      </a>
      <a href="debug.php?clear=1" class="btn danger" onclick="return confirm('Clear all logs? This cannot be undone.');">
        Clear Logs
      </a>
    </div>
    
    <?php if (!empty($logsToDisplay)): ?>
      <div class="stats">
        <div class="stat-card">
          <div class="stat-value"><?php echo count($logsToDisplay); ?></div>
          <div class="stat-label">Log Entries</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $logType === 'payment' ? 'Payment' : 'General'; ?></div>
          <div class="stat-label">Log Type</div>
        </div>
      </div>
    <?php endif; ?>
    
    <div class="logs-container">
      <?php if (empty($logsToDisplay)): ?>
        <div class="empty-state">
          <p>No logs available yet.</p>
          <p style="font-size: 12px; margin-top: 10px;">
            Visit the <a href="pay.php">Pay Bills page</a> and perform some transactions to see debug logs here.
          </p>
        </div>
      <?php else: ?>
        <?php foreach (array_reverse($logsToDisplay) as $log): ?>
          <div class="log-entry <?php echo $logType === 'payment' ? 'json' : 'raw'; ?>">
            <?php if ($logType === 'payment'): ?>
              <div class="log-timestamp">
                <?php 
                  $logData = json_decode(trim($log), true);
                  if ($logData) {
                    echo htmlspecialchars($logData['timestamp'] ?? 'N/A');
                ?>
              </div>
              <div>
                <span class="log-level <?php echo htmlspecialchars($logData['event'] ?? 'INFO'); ?>">
                  <?php echo htmlspecialchars($logData['event'] ?? 'INFO'); ?>
                </span>
                <strong><?php echo htmlspecialchars($logData['event'] ?? 'LOG'); ?></strong>
              </div>
              <pre style="margin-top: 8px; overflow-x: auto;"><?php echo htmlspecialchars(json_encode($logData['data'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                <?php } else { echo htmlspecialchars($log); } ?>
            <?php else: ?>
              <?php echo htmlspecialchars($log); ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    
    <div style="margin-top: 20px; padding: 20px; background: #f0f9ff; border-radius: 8px; border: 1px solid #bae6fd;">
      <h3 style="margin-bottom: 10px; font-size: 14px;">💡 Tips for Debugging</h3>
      <ul style="font-size: 13px; color: #666; line-height: 1.6; margin-left: 20px;">
        <li>Open the browser console (F12) on the Pay Bills page to see client-side debug logs in real-time</li>
        <li>Use <code>window.getDebugLogs()</code> in the browser console to get all JavaScript debug logs</li>
        <li>Use <code>window.exportDebugLogs()</code> in the browser console to export logs as JSON</li>
        <li>Server-side logs are stored in: <code>logs/payment_flow.log</code> and <code>logs/debug.log</code></li>
        <li>Each payment attempt generates events: PAYMENT_ATTEMPT_START → VALIDATION → BALANCE_CHECK → WALLET_UPDATE → TRANSACTION_RECORD → PAYMENT_SUCCESS/PAYMENT_ERROR</li>
      </ul>
    </div>
  </div>
  
  <script>
    // Auto-refresh logs every 2 seconds
    setTimeout(() => {
      location.reload();
    }, 2000);
  </script>
</body>
</html>
