4
1<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/controller/WalletController.php';

$userModel = new User();
$user_id = $_SESSION['user_id'] ?? 1;
$error_msg = null;

try {
  $walletCtrl = new WalletController();
  $balance = $walletCtrl->getWalletBalance($user_id);
  $transactions = $walletCtrl->getTransactionHistory($user_id, 10);
  $user = $userModel->getUserById($user_id);
  $profile_picture = $user['profile_picture'] ?? null;
} catch (Exception $e) {
  $balance = 0.00;
  $transactions = [];
  $profile_picture = null;
  $error_msg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>NepalPay Desktop Wallet</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<style>
  body { 
    background: linear-gradient(135deg, #f8fafc, #eef2ff);
  }
  .glass {
    backdrop-filter: blur(12px);
    background: rgba(255,255,255,0.55);
  }
  .hover-card:hover {
    transform: translateY(-3px);
    transition: .2s ease;
    box-shadow: 0 8px 22px rgba(0,0,0,0.08);
  }
  .clickable-row { cursor: pointer; }
  .clickable-row:hover { background: #f8fafc; }
  .card-link { display:block; color:inherit; text-decoration:none; }
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("transferModal");
  const sendButtons = document.querySelectorAll(".openSendModal");
  const cancelBtn = document.getElementById("cancelBtn");
  const sendForm = document.getElementById("sendForm");
  const balanceElements = document.querySelectorAll(".walletBalance");
  let currentBalance = parseFloat(<?php echo json_encode($balance); ?>);

  function formatRs(amount){
    return "Rs " + amount.toLocaleString('en-IN', {minimumFractionDigits:2});
  }

  sendButtons.forEach(btn => {
    btn.addEventListener("click", () => modal.classList.remove("hidden"));
  });

  cancelBtn.addEventListener("click", () => {
    modal.classList.add("hidden");
    sendForm.reset();
  });

  sendForm.addEventListener("submit", e => {
    e.preventDefault();
    const to = sendForm.to.value.trim();
    const amount = parseFloat(sendForm.amount.value);

    if (!to || !amount || amount <= 0 || amount > currentBalance) {
      alert("Invalid details. Check amount and receiver.");
      return;
    }

    currentBalance -= amount;
    balanceElements.forEach(el => el.textContent = formatRs(currentBalance));

    alert(`Sent Rs ${amount} to ${to}`);
    modal.classList.add("hidden");
    sendForm.reset();
  });

  // Toggle provider field for topup
  const sendType = document.getElementById('sendType');
  sendType.addEventListener('change', function() {
    const providerField = document.getElementById('providerField');
    if (this.value === 'topup' || this.value === 'fulltopup') {
      providerField.classList.remove('hidden');
    } else {
      providerField.classList.add('hidden');
    }
  });
});
</script>

</head>
<body class="font-sans">

<div class="max-w-[1300px] mx-auto grid grid-cols-12 gap-0 shadow-xl rounded-2xl overflow-hidden">

  <!-- Sidebar -->
  <?php
    $user_name = $_SESSION['user_name'] ?? 'User';
  ?>
  <aside class="col-span-3 bg-gradient-to-b from-blue-600 to-blue-700 text-white p-6" role="navigation" aria-label="Main sidebar">
    <div class="flex items-center justify-between gap-3 mb-6">
      <div class="flex items-center gap-3">
        <img src="assets/logo.svg" alt="NepalPay Logo" class="w-12 h-12 rounded-full">
        <div>
          <div class="text-xs opacity-80">Welcome</div>
          <div class="font-semibold text-lg"><?= htmlspecialchars($user_name); ?></div>
        </div>
      </div>

      <!-- Optional collapse control for smaller screens -->
      <button id="sidebarToggle" class="p-2 rounded-md bg-white/10 hover:bg-white/20 focus:outline-none" aria-label="Toggle sidebar" title="Toggle sidebar">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
      </button>
    </div>

    <div class="mb-6">
      <a href="wallet.php" class="block group">
        <p class="text-xs uppercase opacity-75 group-hover:text-white">Balance</p>
        <p class="walletBalance text-3xl font-bold mt-1 group-hover:underline">
          Rs <?php echo number_format($balance,2); ?>
        </p>
      </a>
      <p class="text-xs opacity-80 mt-2">Available for payments</p>
    </div>

    <nav class="space-y-2 text-sm" aria-label="Sidebar links">
      <ul class="space-y-2">
        <li>
          <a href="dashboard.php" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg bg-white/20 hover:bg-white/30 focus:outline-none focus:ring-2 focus:ring-white/30" aria-current="page">
            <span class="inline-flex items-center justify-center w-6 h-6 bg-white/10 rounded">🏠</span>
            <span>Dashboard</span>
          </a>
        </li>

        <li>
          <!-- Keep JS hook class for existing scripts -->
          <a href="#" class="openSendModal flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20" role="button" aria-pressed="false">
            <span class="inline-flex items-center justify-center w-6 h-6 bg-white/10 rounded">➡️</span>
            <span>Send Money</span>
          </a>
        </li>

        <li>
          <a href="wallet.php" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20">
            <span class="inline-flex items-center justify-center w-6 h-6 bg-white/10 rounded">📥</span>
            <span>Receive</span>
          </a>
        </li>

        <li>
          <a href="pay.php" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20">
            <span class="inline-flex items-center justify-center w-6 h-6 bg-white/10 rounded">➕</span>
            <span>Top-Up</span>
          </a>
        </li>

        <li>
          <a href="pay.php" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20">
            <span class="inline-flex items-center justify-center w-6 h-6 bg-white/10 rounded">💡</span>
            <span>Bills</span>
          </a>
        </li>

        <li>
          <a href="wallet.php" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20">
            <span class="inline-flex items-center justify-center w-6 h-6 bg-white/10 rounded">📜</span>
            <span>History</span>
          </a>
        </li>

        <li>
          <a href="profile.php" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20">
            <span class="inline-flex items-center justify-center w-6 h-6 bg-white/10 rounded">⚙️</span>
            <span>Settings</span>
          </a>
        </li>
      </ul>
    </nav>

    <script>
      // Small enhancement: toggle sidebar collapsed state (adds/removes a class on the aside)
      (function(){
        const toggle = document.getElementById('sidebarToggle');
        const aside = toggle ? toggle.closest('aside') : null;
        if (!toggle || !aside) return;
        toggle.addEventListener('click', function(){
          aside.classList.toggle('collapsed');
          // collapsed class can be used in your CSS to reduce width / hide labels on small screens
        });
        // allow keyboard activation for non-anchor send button (if JS replaces anchors)
        document.querySelectorAll('.openSendModal').forEach(el => {
          el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              el.click();
            }
          });
        });
      })();
    </script>
  </aside>

  <!-- Main Content -->
  <main class="col-span-9 p-8 bg-white">

    <header class="flex justify-between items-center">
      <h1 class="text-2xl font-semibold">NepalPay Wallet</h1>
      <div class="flex items-center gap-3">
        <input class="px-3 py-2 border rounded-lg w-72 text-sm" placeholder="Search...">
        <div class="p-2 rounded-full bg-gray-100 cursor-pointer">🔔</div>
        <div class="flex items-center gap-2">
          <div class="text-right text-sm">
            <div class="text-gray-500">Offline</div>
            <div class="font-semibold">Profile</div>
          </div>
          <?php if ($profile_picture): ?>
            <img src="<?= htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="w-10 h-10 rounded-full cursor-pointer openProfileModal">
          <?php else: ?>
            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center cursor-pointer openProfileModal">SG</div>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <!-- Quick cards -->
    <section class="mt-6 grid grid-cols-3 gap-6">

      <div class="col-span-2 grid grid-cols-3 gap-4">
        <div class="p-5 bg-white rounded-2xl shadow hover-card">
          <p class="text-xs text-gray-400 uppercase">Send</p>
          <h3 class="text-lg font-semibold mt-2">Send Money</h3>
          <p class="text-sm mt-2 text-gray-500">Transfer instantly to wallets or banks.</p>
          <button class="openSendModal mt-4 px-4 py-2 rounded-lg bg-indigo-600 text-white">Send Now</button>
        </div>

        <a href="pay.php" class="card-link">
        <div class="p-5 bg-white rounded-2xl shadow hover-card">
          <p class="text-xs text-gray-400 uppercase">Top Up</p>
          <h3 class="text-lg font-semibold mt-2">Add Funds</h3>
          <p class="text-sm mt-2 text-gray-500">Load money from bank/card.</p>
          <div class="mt-4"><span class="mt-4 px-4 py-2 rounded-lg border inline-block">Top-Up</span></div>
        </div>
        </a>

        <a href="pay.php" class="card-link">
        <div class="p-5 bg-white rounded-2xl shadow hover-card">
          <p class="text-xs text-gray-400 uppercase">Pay</p>
          <h3 class="text-lg font-semibold mt-2">Bills & Services</h3>
          <p class="text-sm mt-2 text-gray-500">Electricity, Internet, Mobile.</p>
          <div class="mt-4"><span class="mt-4 px-4 py-2 rounded-lg border inline-block">Pay Bill</span></div>
        </div>
        </a>

        <div class="p-5 bg-white rounded-2xl shadow hover-card">
          <p class="text-xs text-gray-400 uppercase">Security</p>
          <h3 class="text-lg font-semibold mt-2">Change Password</h3>
          <p class="text-sm mt-2 text-gray-500">Update your account password.</p>
          <button class="openPasswordModal mt-4 px-4 py-2 rounded-lg bg-purple-600 text-white">Change Password</button>
        </div>
      </div>

      <div class="p-5 glass rounded-2xl shadow">
        <p class="text-xs text-gray-500 uppercase">Wallet Balance</p>
        <p class="walletBalance text-3xl font-bold mt-2">
          Rs <?php echo number_format($balance,2); ?>
        </p>
        <p class="mt-2 text-gray-500 text-sm">Available for payments</p>
        <div class="mt-4 flex gap-3">
          <button id="receiveBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg">Receive</button>
          <button class="openSendModal px-4 py-2 bg-red-600 text-white rounded-lg">Send</button>
        </div>


      </div>

    </section>

    <!-- Transactions -->
    <section class="mt-8 grid grid-cols-2 gap-6">

      <div class="bg-white p-5 rounded-2xl shadow-sm">
        <div class="flex justify-between">
          <div>
            <p class="text-sm text-gray-500">Recent Transactions</p>
            <h3 class="font-semibold">Activity</h3>

            <?php if ($error_msg): ?>
              <div class="mt-3 p-3 bg-yellow-50 text-yellow-800 rounded text-sm">
                Error: <?= htmlspecialchars($error_msg); ?>
              </div>
            <?php endif; ?>

          </div>
          <span class="text-indigo-600 text-sm cursor-pointer">View All</span>
        </div>

        <ul class="mt-4 divide-y">
          <?php foreach ($transactions as $tx): ?>
            <?php $link = !empty($tx['txn_id']) ? 'receipt.php?txn=' . urlencode($tx['txn_id']) : '#'; ?>
            <li class="py-3">
              <a href="<?= $link; ?>" class="flex justify-between items-center p-2 rounded-lg card-link clickable-row">
                <div>
                  <p class="font-medium"><?= htmlspecialchars($tx["description"] ?: $tx["type"]); ?></p>
                  <p class="text-xs text-gray-500"><?= $tx["created_at"]; ?> • <?= $tx["type"]; ?><?= !empty($tx['provider']) ? ' • ' . htmlspecialchars($tx['provider']) : ''; ?></p>
                  <?php if (!empty($tx['customer_ref'])): ?><div class="text-xs text-gray-400">Customer: <?= htmlspecialchars($tx['customer_ref']); ?></div><?php endif; ?>
                </div>

                <div class="text-right">
                  <p class="font-semibold <?= $tx["amount"]<0 ? "text-red-500" : "text-green-600"; ?>">Rs <?= number_format(abs($tx["amount"]),2); ?></p>
                  <?php if (!empty($tx['txn_id'])): ?><div class="text-xs text-indigo-600">Txn: <?= htmlspecialchars($tx['txn_id']); ?></div><?php endif; ?>
                </div>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="bg-white p-5 rounded-2xl shadow-sm">
        <p class="text-sm text-gray-500">Quick Send To</p>
        <div class="mt-4 grid grid-cols-3 gap-3">
          <?php foreach (["Ram","Sita","Shop","Bank","Mom","Dad"] as $c): ?>
            <button class="p-3 bg-gray-50 rounded-lg text-sm hover:bg-gray-100"><?= $c; ?></button>
          <?php endforeach; ?>
        </div>

        <p class="mt-6 text-sm text-gray-500">Offers & Rewards</p>
        <div class="mt-3 p-3 bg-indigo-50 rounded-lg">Get 5% cashback on select bill payments.</div>
      </div>

    </section>

    <footer class="mt-8 text-center text-xs text-gray-400">
      NepalPay UI Demo • Not affiliated with eSewa / Khalti
    </footer>

  </main>
</div>

<!-- Transfer Modal -->
<div id="transferModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-6">
  <div class="bg-white p-6 rounded-2xl w-full max-w-md">
    <h2 class="text-lg font-semibold">Send Money</h2>

    <form id="sendForm" class="mt-4 space-y-3">
      <div>
        <label class="text-xs text-gray-500">Type</label>
        <select name="type" id="sendType" class="w-full border px-3 py-2 rounded">
          <option value="phone">Send to Phone</option>
          <option value="wallet">Send to Wallet</option>
          <option value="bank">Send to Bank</option>
          <option value="merchant">Pay Merchant</option>
          <option value="utilities">Pay Utilities</option>
          <option value="bills">Pay Bills</option>
          <option value="internet">Pay Internet</option>
          <option value="electricity">Pay Electricity</option>
          <option value="topup">Top-Up</option>
          //<option value="fulltopup">Full Top-Up</option>
        </select>
      </div>
      <div id="providerField" class="hidden">
        <label class="text-xs text-gray-500">Provider</label>
        <select name="provider" class="w-full border px-3 py-2 rounded">
          <option value="2">NCell</option>
          <option value="4">NTC</option>
          <option value="5">SmartCell</option>
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-500">To</label>
        <input name="to" id="sendTo" class="w-full border px-3 py-2 rounded" placeholder="Phone, wallet ID, account number">
      </div>
      <div>
        <label class="text-xs text-gray-500">Amount (Rs)</label>
        <input name="amount" type="number" step="0.01" class="w-full border px-3 py-2 rounded">
      </div>
      <div>
        <label class="text-xs text-gray-500">Note</label>
        <input name="note" class="w-full border px-3 py-2 rounded">
      </div>

      <div class="flex justify-end gap-3 mt-4">
        <button type="button" id="cancelBtn" class="px-4 py-2 border rounded-lg">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Send</button>
      </div>
    </form>
  </div>
</div>

<!-- Receive Modal -->
<div id="receiveModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-6">
  <div class="bg-white p-6 rounded-2xl w-full max-w-md">
    <h2 class="text-lg font-semibold">Receive Money</h2>

    <form id="receiveForm" class="mt-4 space-y-3">
      <div>
        <label class="text-xs text-gray-500">Provider</label>
        <select id="receive_provider" class="w-full border px-3 py-2 rounded">
          <option value="2">NCell</option>
          <option value="4">NTC</option>
          <option value="5">SmartCell</option>
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-500">Mobile Number</label>
        <input name="reference" id="receive_reference" class="w-full border px-3 py-2 rounded" placeholder="Enter phone number">
      </div>
      <div>
        <label class="text-xs text-gray-500">Amount (Rs)</label>
        <input name="amount" type="number" step="0.01" min="1" class="w-full border px-3 py-2 rounded">
      </div>

      <div class="flex justify-end gap-3 mt-4">
        <button type="button" id="receiveCancel" class="px-4 py-2 border rounded-lg">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">Request</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>

<!-- Top-up Modal -->
<div id="topupModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-6">
  <div class="bg-white p-6 rounded-2xl w-full max-w-md">
    <h2 class="text-lg font-semibold">Quick Top-Up</h2>

    <form id="topupForm" method="POST" action="pay.php" class="mt-4 space-y-3">
      <input type="hidden" name="biller_id" id="topup_biller_id" value="">
      <div>
        <label class="text-xs text-gray-500">Provider</label>
        <input id="topup_provider_name" class="w-full border px-3 py-2 rounded" readonly>
      </div>

      <div>
        <label class="text-xs text-gray-500">Mobile/Account Number</label>
        <input name="reference" id="topup_reference" class="w-full border px-3 py-2 rounded" placeholder="Enter phone or account number">
      </div>

      <div>
        <label class="text-xs text-gray-500">Amount (Rs)</label>
        <input name="amount" id="topup_amount" type="number" step="0.01" min="1" class="w-full border px-3 py-2 rounded">
      </div>

      <input type="hidden" name="method" value="nepalpay">

      <div class="flex justify-end gap-3 mt-4">
        <button type="button" id="topupCancel" class="px-4 py-2 border rounded-lg">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Top-Up</button>
      </div>
    </form>
  </div>
</div>

<!-- Password Change Modal -->
<div id="passwordModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-6">
  <div class="bg-white p-6 rounded-2xl w-full max-w-md">
    <h2 class="text-lg font-semibold">Change Password</h2>

    <form id="passwordForm" method="POST" action="change_password.php" class="mt-4 space-y-3">
      <div>
        <label class="text-xs text-gray-500">Current Password</label>
        <input name="current_password" type="password" class="w-full border px-3 py-2 rounded" required>
      </div>

      <div>
        <label class="text-xs text-gray-500">New Password</label>
        <input name="new_password" type="password" class="w-full border px-3 py-2 rounded" required>
      </div>

      <div>
        <label class="text-xs text-gray-500">Confirm New Password</label>
        <input name="confirm_password" type="password" class="w-full border px-3 py-2 rounded" required>
      </div>

      <div class="flex justify-end gap-3 mt-4">
        <button type="button" id="passwordCancel" class="px-4 py-2 border rounded-lg">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg">Change Password</button>
      </div>
    </form>
  </div>
</div>

<!-- Profile Picture Change Modal -->
<div id="profileModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-6">
  <div class="bg-white p-6 rounded-2xl w-full max-w-md">
    <h2 class="text-lg font-semibold">Change Profile Picture</h2>

    <form id="profileForm" method="POST" action="change_profile.php" enctype="multipart/form-data" class="mt-4 space-y-3">
      <div>
        <label class="text-xs text-gray-500">Upload New Picture</label>
        <input name="profile_picture" type="file" accept="image/*" class="w-full border px-3 py-2 rounded" required>
      </div>

      <div class="flex justify-end gap-3 mt-4">
        <button type="button" id="profileCancel" class="px-4 py-2 border rounded-lg">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Change Picture</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // Ensure sidebar links always navigate (fallback if other handlers call preventDefault)
  const asideLinks = document.querySelectorAll('aside nav a');
  asideLinks.forEach(a => {
    // Active state based on pathname
    try {
      const hrefPath = new URL(a.href, window.location.href).pathname.replace(/^\//, '');
      const current = window.location.pathname.replace(/^\//, '');
      if (hrefPath === current || a.getAttribute('aria-current') === 'page') {
        a.classList.add('bg-white/30');
      }
    } catch (e) {}

    // Use capture phase and stop propagation so this runs before other handlers
    const navNow = function(evt){
      try { evt.preventDefault(); evt.stopImmediatePropagation(); } catch (e) {}
      window.location.href = a.href;
    };
    a.addEventListener('click', navNow, true);
    a.addEventListener('mousedown', navNow, true);
  });
  const receiveModal = document.getElementById('receiveModal');
  const receiveBtn = document.getElementById('receiveBtn');
  const receiveCancel = document.getElementById('receiveCancel');
  const receiveProvider = document.getElementById('receive_provider');
  const receiveReference = document.getElementById('receive_reference');
  const receiveForm = document.getElementById('receiveForm');

  // Receive modal functionality
  if (receiveBtn) {
    receiveBtn.addEventListener('click', function(){
      receiveModal.classList.remove('hidden');
    });
  }

  if (receiveCancel) {
    receiveCancel.addEventListener('click', function(){
      receiveModal.classList.add('hidden');
      receiveForm.reset();
    });
  }

  if (receiveForm) {
    receiveForm.addEventListener('submit', function(e){
      e.preventDefault();
      const provider = receiveProvider.value;
      const reference = receiveReference.value.trim();
      const amount = parseFloat(receiveForm.amount.value);

      if (!reference || !amount || amount <= 0) {
        alert('Please fill in all fields correctly.');
        return;
      }

      // Validate phone number prefix based on provider
      if (provider === '2' && !reference.startsWith('98')) {
        alert('NCell number must start with 98');
        return;
      }
      if (provider === '4' && !reference.startsWith('97')) {
        alert('NTC number must start with 97');
        return;
      }
      if (provider === '5' && !reference.startsWith('96')) {
        alert('SmartCell number must start with 96');
        return;
      }

      alert(`Request sent for Rs ${amount} to ${reference}`);
      receiveModal.classList.add('hidden');
      receiveForm.reset();
    });
  }

  const topupModal = document.getElementById('topupModal');
  const topupBtns = document.querySelectorAll('.topupBtn');
  const topupCancel = document.getElementById('topupCancel');
  const topupBiller = document.getElementById('topup_biller_id');
  const topupProvName = document.getElementById('topup_provider_name');
  const providerSearch = document.getElementById('providerSearch');

  const providerMap = {
    '2': 'NCell',
    '4': 'NTC (Nepal Telecom)',
    '5': 'SmartCell'
  };

  // Function to detect provider based on phone number
  function detectProvider(phoneNumber) {
    if (phoneNumber.startsWith('97')) {
      return '4'; // NTC
    } else if (phoneNumber.startsWith('98')) {
      return '2'; // NCell
    } else if (phoneNumber.startsWith('96')) {
      return '5'; // SmartCell
    }
    return null; // Unknown
  }

  topupBtns.forEach(b => b.addEventListener('click', function(){
    const id = this.getAttribute('data-biller');
    topupBiller.value = id;
    topupProvName.value = providerMap[id] || '';
    // Prepend prefix based on provider
    let prefix = '';
    if (id === '2') prefix = '98'; // NCell
    else if (id === '4') prefix = '97'; // NTC
    else if (id === '5') prefix = '96'; // SmartCell
    topupReference.value = prefix;
    topupModal.classList.remove('hidden');
    document.getElementById('topup_reference').focus();
  }));

  topupCancel.addEventListener('click', function(){ topupModal.classList.add('hidden'); });

  // Auto-detect provider based on phone number input
  const topupReference = document.getElementById('topup_reference');
  if (topupReference) {
    topupReference.addEventListener('input', function(){
      const phoneNumber = this.value.trim();
      if (phoneNumber.length >= 2) {
        const detectedId = detectProvider(phoneNumber);
        if (detectedId) {
          topupBiller.value = detectedId;
          topupProvName.value = providerMap[detectedId] || '';
        }
      }
    });
  }

  if (providerSearch) {
    providerSearch.addEventListener('input', function(){
      const q = this.value.toLowerCase().trim();
      if (!q) return;
      for (const id in providerMap) {
        if (providerMap[id].toLowerCase().indexOf(q) !== -1) {
          // simulate click on matching provider
          document.querySelector('.topupBtn[data-biller="'+id+'"]')?.click();
          this.value = '';
          break;
        }
      }
    });
  }

  // Validate phone number prefix on topup form submit
  const topupForm = document.getElementById('topupForm');
  if (topupForm) {
    topupForm.addEventListener('submit', function(e){
      const biller = topupBiller.value;
      const reference = topupReference.value.trim();

      if (!reference) {
        alert('Please enter a phone number.');
        e.preventDefault();
        return;
      }

      if (biller === '2' && !reference.startsWith('98')) {
        alert('NCell number must start with 98');
        e.preventDefault();
        return;
      }

      if (biller === '4' && !reference.startsWith('97')) {
        alert('NTC number must start with 97');
        e.preventDefault();
        return;
      }

      if (biller === '5' && !reference.startsWith('96')) {
        alert('SmartCell number must start with 96');
        e.preventDefault();
        return;
      }
    });
  }

  // Password change modal
  const passwordModal = document.getElementById('passwordModal');
  const passwordBtns = document.querySelectorAll('.openPasswordModal');
  const passwordCancel = document.getElementById('passwordCancel');
  const passwordForm = document.getElementById('passwordForm');

  passwordBtns.forEach(btn => btn.addEventListener('click', function(){
    passwordModal.classList.remove('hidden');
  }));

  passwordCancel.addEventListener('click', function(){
    passwordModal.classList.add('hidden');
    passwordForm.reset();
  });

  passwordForm.addEventListener('submit', function(e){
    e.preventDefault();
    const currentPassword = passwordForm.current_password.value;
    const newPassword = passwordForm.new_password.value;
    const confirmPassword = passwordForm.confirm_password.value;

    if (!currentPassword || !newPassword || !confirmPassword) {
      alert('Please fill in all fields.');
      return;
    }

    if (newPassword !== confirmPassword) {
      alert('New password and confirmation do not match.');
      return;
    }

    if (newPassword.length < 6) {
      alert('New password must be at least 6 characters long.');
      return;
    }

    // Here you would typically send the data to the server
    alert('Password change functionality would be implemented here.');
    passwordModal.classList.add('hidden');
    passwordForm.reset();
  });

  // Profile picture change modal
  const profileModal = document.getElementById('profileModal');
  const profileBtns = document.querySelectorAll('.openProfileModal');
  const profileCancel = document.getElementById('profileCancel');
  const profileForm = document.getElementById('profileForm');

  profileBtns.forEach(btn => btn.addEventListener('click', function(){
    profileModal.classList.remove('hidden');
  }));

  profileCancel.addEventListener('click', function(){
    profileModal.classList.add('hidden');
    profileForm.reset();
  });

  profileForm.addEventListener('submit', function(e){
    e.preventDefault();
    const fileInput = profileForm.profile_picture;
    const file = fileInput.files[0];

    if (!file) {
      alert('Please select a file.');
      return;
    }

    // Validate file type
    if (!file.type.startsWith('image/')) {
      alert('Please select a valid image file.');
      return;
    }

    // Validate file size (e.g., max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert('File size must be less than 5MB.');
      return;
    }

    // Here you would typically send the data to the server
    alert('Profile picture change functionality would be implemented here.');
    profileModal.classList.add('hidden');
    profileForm.reset();
  });
});
