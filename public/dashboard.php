<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/controller/WalletController.php';

$user_id = $_SESSION['user_id'] ?? 1;
$error_msg = null;

try {
  $walletCtrl = new WalletController();
  $balance = $walletCtrl->getWalletBalance($user_id);
  $transactions = $walletCtrl->getTransactionHistory($user_id, 10);
} catch (Exception $e) {
  $balance = 0.00;
  $transactions = [];
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
});
</script>

</head>
<body class="font-sans">

<div class="max-w-[1300px] mx-auto grid grid-cols-12 gap-0 shadow-xl rounded-2xl overflow-hidden">

  <!-- Sidebar -->
  <?php
    $user_name = $_SESSION['user_name'] ?? 'User';
  ?>
  <aside class="col-span-3 bg-gradient-to-b from-red-600 to-red-700 text-white p-6" role="navigation" aria-label="Main sidebar">
    <div class="flex items-center justify-between gap-3 mb-6">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center text-xl font-bold">NP</div>
        <div>
          <div class="text-xs opacity-80">Welcome</div>
          <div class="font-semibold text-lg"><?=  ?> htmlspecialchars($user_name); ?></div>
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
          <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">SG</div>
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
      </div>

      <div class="p-5 glass rounded-2xl shadow">
        <p class="text-xs text-gray-500 uppercase">Wallet Balance</p>
        <p class="walletBalance text-3xl font-bold mt-2">
          Rs <?php echo number_format($balance,2); ?>
        </p>
        <p class="mt-2 text-gray-500 text-sm">Available for payments</p>
        <div class="mt-4 flex gap-3">
          <button class="px-4 py-2 bg-green-600 text-white rounded-lg">Receive</button>
          <button class="openSendModal px-4 py-2 bg-red-600 text-white rounded-lg">Send</button>
        </div>

        <!-- Quick Top-up -->
        <div class="mt-5">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">Quick Top-Up</div>
            <a href="pay.php" class="text-indigo-600 text-sm">Full top-up</a>
          </div>

          <div class="mt-3 grid grid-cols-3 gap-2">
            <button class="topupBtn p-3 bg-white/80 border rounded-lg text-sm hover:bg-white" data-biller="2">NCell</button>
            <button class="topupBtn p-3 bg-white/80 border rounded-lg text-sm hover:bg-white" data-biller="4">NTC</button>
            <button class="topupBtn p-3 bg-white/80 border rounded-lg text-sm hover:bg-white" data-biller="5">SmartCell</button>
          </div>

          <div class="mt-3">
            <input id="providerSearch" type="search" placeholder="Find provider (NTC, NCell, SmartCell)" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>
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
        <label class="text-xs text-gray-500">To</label>
        <input name="to" class="w-full border px-3 py-2 rounded">
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

  topupBtns.forEach(b => b.addEventListener('click', function(){
    const id = this.getAttribute('data-biller');
    topupBiller.value = id;
    topupProvName.value = providerMap[id] || '';
    topupModal.classList.remove('hidden');
    document.getElementById('topup_amount').focus();
  }));

  topupCancel.addEventListener('click', function(){ topupModal.classList.add('hidden'); });

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
});
</script>
