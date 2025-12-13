<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/controller/BillController.php';
require_once __DIR__ . '/../app/controller/WalletController.php';

// Mock user id for demo – replace with real session user id
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

$billCtrl = new BillController();
$walletCtrl = new WalletController();

// Mock billers list — in a real app this would come from DB
$billers = [
    1 => ['name' => 'Nepal Electricity Authority', 'type' => 'electricity', 'nepalpay_phone' => '9800000001', 'bank' => 'NEA Bank A/C 1001'],
    2 => ['name' => 'NCell Mobile', 'type' => 'mobile', 'nepalpay_phone' => '9800000002', 'bank' => 'NCell Bank A/C 2001'],
    3 => ['name' => 'WorldLink Internet', 'type' => 'internet', 'nepalpay_phone' => '9800000003', 'bank' => 'WorldLink Bank A/C 3001'],
    4 => ['name' => 'NTC (Nepal Telecom)', 'type' => 'mobile', 'nepalpay_phone' => '9800000004', 'bank' => 'NTC Bank A/C 4001'],
    5 => ['name' => 'SmartCell', 'type' => 'mobile', 'nepalpay_phone' => '9800000005', 'bank' => 'SmartCell Bank A/C 5001'],
    6 => ['name' => 'DishHome TV', 'type' => 'television', 'nepalpay_phone' => '9800000006', 'bank' => 'DishHome Bank A/C 6001'],
    7 => ['name' => 'Kathmandu Upatyaka Khanepani Limited (KUKL)', 'type' => 'water', 'nepalpay_phone' => '9800000007', 'bank' => 'KUKL Bank A/C 7001'],
    8 => ['name' => 'Ncell Top-up', 'type' => 'mobile_topup', 'nepalpay_phone' => '9800000008', 'bank' => 'NCell Bank A/C 8001'],
    9 => ['name' => 'NTC Top-up', 'type' => 'mobile_topup', 'nepalpay_phone' => '9800000009', 'bank' => 'NTC Bank A/C 9001'],
    10 => ['name' => 'SmartCell Top-up', 'type' => 'mobile_topup', 'nepalpay_phone' => '9800000010', 'bank' => 'SmartCell Bank A/C 10001'],
    11 => ['name' => 'IME Pay', 'type' => 'wallet', 'nepalpay_phone' => '9800000011', 'bank' => 'IME Bank A/C 11001'],
    12 => ['name' => 'Prabhu Pay', 'type' => 'wallet', 'nepalpay_phone' => '9800000012', 'bank' => 'Prabhu Bank A/C 12001'],
    13 => ['name' => 'Government Tax Payment', 'type' => 'government', 'nepalpay_phone' => '9800000013', 'bank' => 'IRD Bank A/C 13001'],
    14 => ['name' => 'Insurance Premium', 'type' => 'insurance', 'nepalpay_phone' => '9800000014', 'bank' => 'Insurance Bank A/C 14001'],
    15 => ['name' => 'Education Fees', 'type' => 'education', 'nepalpay_phone' => '9800000015', 'bank' => 'Education Bank A/C 15001'],
    16 => ['name' => 'Broadband Internet', 'type' => 'internet', 'nepalpay_phone' => '9800000016', 'bank' => 'Broadband Bank A/C 16001'],
    17 => ['name' => 'Mobile Data Pack', 'type' => 'mobile_data', 'nepalpay_phone' => '9800000017', 'bank' => 'Data Bank A/C 17001'],
    18 => ['name' => 'Gas Cylinder', 'type' => 'gas', 'nepalpay_phone' => '9800000018', 'bank' => 'Gas Bank A/C 18001'],
    19 => ['name' => 'Cable TV', 'type' => 'television', 'nepalpay_phone' => '9800000019', 'bank' => 'Cable Bank A/C 19001'],
    20 => ['name' => 'Municipal Tax', 'type' => 'government', 'nepalpay_phone' => '9800000020', 'bank' => 'Municipal Bank A/C 20001'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $biller_id = intval($_POST['biller_id']);
  $amount = floatval($_POST['amount']);
  $method = $_POST['method'];
  $reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';

  if (!isset($billers[$biller_id])) {
    setFlash('error', 'Selected biller is invalid.');
    header('Location: pay.php'); exit;
  }

  $result = $billCtrl->payBill($user_id, $biller_id, $amount, $method, $reference);
  // If result is a txn id (string), redirect to receipt page
  if ($result && is_string($result)) {
    header('Location: receipt.php?txn=' . urlencode($result));
    exit;
  }
  // otherwise go back to pay page (flash set by controller)
  header('Location: pay.php');
  exit;
}

// For display, get current wallet balance
$balance = $walletCtrl->getWalletBalance($user_id);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pay Bills — NepalPay</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="container" style="max-width:900px;margin:36px auto;padding:28px;">
    <h2>Pay Bills & Utilities</h2>
    <?php
      $flash = getFlash('success'); if ($flash) echo '<div class="flash-message flash-success">' . htmlspecialchars($flash) . '</div>';
      $err = getFlash('error'); if ($err) echo '<div class="flash-message flash-error">' . htmlspecialchars($err) . '</div>';
    ?>

    <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
      <div style="flex:1;min-width:300px;">
        <div style="background:#fff;padding:18px;border-radius:8px;border:1px solid #eef2f6;box-shadow:0 6px 18px rgba(0,0,0,0.04);">
          <div style="margin-bottom:8px;color:#6b7280">Available balance</div>
          <div style="font-size:24px;font-weight:700;color:#d32f2f">Rs <?php echo number_format($balance,2); ?></div>
        </div>

        <div style="margin-top:18px;background:#fff;padding:18px;border-radius:8px;border:1px solid #eef2f6;">
          <h4 style="margin:0 0 8px">Quick billers</h4>
          <?php foreach($billers as $id=>$b): ?>
            <div style="display:flex;justify-content:space-between;padding:8px 6px;border-bottom:1px solid #f1f5f9;">
              <div>
                <div style="font-weight:600"><?php echo htmlspecialchars($b['name']); ?></div>
                <div style="font-size:13px;color:#6b7280"><?php echo htmlspecialchars($b['type']); ?></div>
              </div>
              <div><button class="btn-transfer" onclick="selectBiller(<?php echo $id; ?>)">Pay</button></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="width:420px;min-width:300px;">
        <form method="POST" id="payForm" style="background:#fff;padding:18px;border-radius:8px;border:1px solid #eef2f6;">
          <h4 style="margin-top:0">Make a payment</h4>

          <label style="display:block;margin-top:8px">Biller</label>
          <select name="biller_id" id="biller_id" style="width:100%;padding:10px;border-radius:8px;border:1px solid #dfe6ef">
            <option value="">-- Select biller --</option>
            <?php foreach($billers as $id=>$b): ?>
              <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
            <?php endforeach; ?>
          </select>

          <label style="display:block;margin-top:8px">Amount (Rs)</label>
          <input name="amount" id="amount" type="number" step="0.01" min="1" style="width:100%;padding:10px;border-radius:8px;border:1px solid #dfe6ef">

          <label style="display:block;margin-top:8px">Payment method</label>
          <select name="method" id="method" style="width:100%;padding:10px;border-radius:8px;border:1px solid #dfe6ef">
            <option value="nepalpay">NepalPay (wallet)</option>
            <option value="bank">Bank account</option>
          </select>

          <label style="display:block;margin-top:8px">Reference (optional)</label>
          <input name="reference" id="reference" type="text" style="width:100%;padding:10px;border-radius:8px;border:1px solid #dfe6ef">

          <div style="display:flex;gap:8px;align-items:center;margin-top:8px;">
            <input id="sc_no" type="text" placeholder="Enter SC No (for NEA)" style="flex:1;padding:10px;border-radius:8px;border:1px solid #dfe6ef">
            <button type="button" class="btn-transfer" id="lookupBtn">Lookup</button>
          </div>

          <div style="display:flex;gap:8px;margin-top:12px;">
            <button type="submit" class="btn-payment">Pay Now</button>
            <button type="button" class="btn-transfer" onclick="document.getElementById('payForm').reset();">Reset</button>
          </div>

          <div id="biller_info" style="margin-top:12px;font-size:13px;color:#6b7280"></div>
        </form>
      </div>
    </div>

  </div>

<script>
  const billers = <?php echo json_encode($billers); ?>;
  function selectBiller(id){
    document.getElementById('biller_id').value = id;
    updateBillerInfo();
    window.scrollTo({top:0,behavior:'smooth'});
  }
  function updateBillerInfo(){
    const sel = document.getElementById('biller_id');
    const info = document.getElementById('biller_info');
    const id = sel.value;
    if(!id){ info.textContent = ''; return; }
    const b = billers[id];
    info.innerHTML = '<strong>'+b.name+'</strong><div> NepalPay ID: '+b.nepalpay_phone+'</div><div>Bank: '+b.bank+'</div>';
  }
  document.getElementById('biller_id').addEventListener('change', updateBillerInfo);

  // Lookup NEA bill by SC No
  document.getElementById('lookupBtn').addEventListener('click', function(){
    const sc = document.getElementById('sc_no').value.trim();
    if(!sc){ alert('Enter SC No to lookup'); return; }
    this.disabled = true; this.textContent = 'Looking...';
    fetch('api/get_bill.php?sc_no=' + encodeURIComponent(sc))
      .then(r => r.json())
      .then(json => {
        if(json.ok && json.data){
          // Try to populate amount and reference if available
          const d = json.data;
          if(d.amount) document.getElementById('amount').value = parseFloat(d.amount);
          if(d.consumer_name) document.getElementById('reference').value = d.consumer_name + ' / ' + sc;
          alert('Bill found: ' + (d.consumer_name || 'unknown') + '\nAmount: ' + (d.amount || 'N/A'));
        } else {
          alert('Lookup failed: ' + (json.error || 'No data'));
        }
      })
      .catch(err => { alert('Lookup error: ' + err.message); })
      .finally(()=>{ document.getElementById('lookupBtn').disabled = false; document.getElementById('lookupBtn').textContent = 'Lookup'; });
  });

  // Restrict available methods to nepalpay and bank (already done), but show warning if other selected
  document.getElementById('payForm').addEventListener('submit', function(e){
    const method = document.getElementById('method').value;
    if(['nepalpay','bank'].indexOf(method) === -1){ e.preventDefault(); alert('Only NepalPay and Bank account payments are allowed.'); }
    const billerId = document.getElementById('biller_id').value;
    const amount = parseFloat(document.getElementById('amount').value);
    if(!billerId || !amount || amount <= 0){ e.preventDefault(); alert('Please select a biller and enter a valid amount.'); }
  });
</script>
</body>
</html>
