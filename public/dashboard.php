<?php
// Ensure session helper is loaded first (starts session)
require_once __DIR__ . '/../app/helpers/session_helper.php';
// Ensure User model is available (some includes may expect it)
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/controller/WalletController.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
try {
  $walletCtrl = new WalletController();
  $balance = $walletCtrl->getWalletBalance($user_id);
  $transactions = $walletCtrl->getTransactionHistory($user_id, 10);
} catch (Exception $e) {
  // Prevent fatal errors from breaking the page; show minimal fallback
  $balance = 0.00;
  $transactions = [];
  $error_msg = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NepalPay Wallet — Demo</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const sendMoneyBtn = document.getElementById('sendMoneyBtn');
      const sendMoneyBtn2 = document.getElementById('sendMoneyBtn2');
      const transferModal = document.getElementById('transferModal');
      const cancelBtn = document.getElementById('cancelBtn');
      const sendForm = document.getElementById('sendForm');
      const balanceElements = document.querySelectorAll('#walletBalance, #walletBalanceWidget');
      let currentBalance = parseFloat(<?php echo json_encode($balance); ?>);

      function formatRs(amount) {
        return 'Rs ' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }

      if (sendMoneyBtn) {
        sendMoneyBtn.addEventListener('click', () => {
          transferModal.classList.remove('hidden');
        });
      }
      if (sendMoneyBtn2) {
        sendMoneyBtn2.addEventListener('click', () => {
          transferModal.classList.remove('hidden');
        });
      }

      cancelBtn.addEventListener('click', () => {
        transferModal.classList.add('hidden');
        sendForm.reset();
      });

      sendForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const to = sendForm.to.value.trim();
        const amount = parseFloat(sendForm.amount.value);
        if (!to || !amount || amount <= 0 || amount > currentBalance) {
          alert('Please enter valid details (amount must be positive and less than or equal to balance)');
          return;
        }
        currentBalance = +(currentBalance - amount).toFixed(2);
        balanceElements.forEach(el => el.textContent = formatRs(currentBalance));
        alert(`Sent Rs ${amount} to ${to}`);
        transferModal.classList.add('hidden');
        sendForm.reset();
      });
    });
  </script>
</head>
<body class="min-h-screen bg-gradient-to-tr from-gray-50 to-gray-100 p-6 font-sans text-gray-800">
  <div class="max-w-[1200px] mx-auto bg-white rounded-2xl shadow-lg overflow-hidden grid grid-cols-12">
    <!-- Sidebar -->
    <aside class="col-span-3 bg-gradient-to-b from-red-600 to-red-700 text-white p-6">
      <div class="flex items-center gap-3 mb-8">
        <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-xl font-bold">NP</div>
        <div>
          <div class="text-sm opacity-90">Welcome,</div>
          <div class="font-semibold">User</div>
        </div>
      </div>

      <div class="mb-6">
        <div class="text-xs uppercase opacity-90">Balance</div>
          <div class="text-3xl font-bold mt-1" id="walletBalance">
          <?php echo 'Rs ' . number_format($balance, 2); ?>
        </div>
      </div>

      <nav class="space-y-2 text-sm">
        <button class="w-full text-left px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20">Dashboard</button>
        <button class="w-full text-left px-3 py-2 rounded-lg hover:bg-white/5">Send Money</button>
        <button class="w-full text-left px-3 py-2 rounded-lg hover:bg-white/5">Receive</button>
        <button class="w-full text-left px-3 py-2 rounded-lg hover:bg-white/5">Top-Up</button>
        <button class="w-full text-left px-3 py-2 rounded-lg hover:bg-white/5">Bills</button>
        <button class="w-full text-left px-3 py-2 rounded-lg hover:bg-white/5">History</button>
        <button class="w-full text-left px-3 py-2 rounded-lg hover:bg-white/5">Settings</button>
      </nav>

      <div class="mt-6 text-xs opacity-90">
        <div class="mb-2">Quick links</div>
        <div class="flex gap-2">
          <button class="px-2 py-1 bg-white/10 rounded">KYC</button>
          <button class="px-2 py-1 bg-white/10 rounded">Offers</button>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <main class="col-span-9 p-6">
      <header class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <h1 class="text-xl font-semibold">NepalPay — Desktop Wallet</h1>
          <div class="text-sm text-gray-500">Personal Wallet • Secure • Nepali UI</div>
        </div>

        <div class="flex items-center gap-3">
          <input placeholder="Search transactions, contacts..." class="px-3 py-2 border rounded-lg text-sm w-72" />
          <div class="p-2 rounded-full bg-gray-100">🔔</div>
          <div class="flex items-center gap-2">
            <div class="text-right text-sm">
              <div>Offline</div>
              <div class="font-semibold">Profile</div>
            </div>
            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">SG</div>
          </div>
        </div>
      </header>

      <section class="mt-6 grid grid-cols-3 gap-6">
        <!-- Quick action cards -->
        <div class="col-span-2 grid grid-cols-3 gap-4">
          <div class="p-5 bg-white rounded-2xl shadow-sm">
            <div class="text-xs uppercase text-gray-400">Send</div>
            <div class="mt-3 font-semibold text-lg">Send Money</div>
            <div class="mt-4 text-sm text-gray-500">Send money to bank or e-sewa ID instantly.</div>
            <div class="mt-4">
              <button id="sendMoneyBtn" class="px-4 py-2 rounded-lg bg-indigo-600 text-white">Send Now</button>
            </div>
          </div>

          <div class="p-5 bg-white rounded-2xl shadow-sm">
            <div class="text-xs uppercase text-gray-400">Top-up</div>
            <div class="mt-3 font-semibold text-lg">Top-Up</div>
            <div class="mt-4 text-sm text-gray-500">Add money from bank or card.</div>
            <div class="mt-4">
              <a href="pay.php" class="px-4 py-2 rounded-lg border inline-block">Top-Up</a>
            </div>
          </div>

          <div class="p-5 bg-white rounded-2xl shadow-sm">
            <div class="text-xs uppercase text-gray-400">Pay</div>
            <div class="mt-3 font-semibold text-lg">Bills & Recharge</div>
            <div class="mt-4 text-sm text-gray-500">Mobile, Electricity, Internet.</div>
            <div class="mt-4">
              <a href="pay.php" class="px-4 py-2 rounded-lg border inline-block">Pay Bill</a>
            </div>
          </div>
        </div>

        <!-- Balance widget -->
        <div class="p-5 bg-gradient-to-b from-yellow-50 to-white rounded-2xl shadow-sm">
          <div class="text-xs uppercase text-gray-400">Wallet Balance</div>
          <div class="mt-3 font-bold text-2xl" id="walletBalanceWidget"><?php echo 'Rs ' . number_format($balance, 2); ?></div>
          <div class="mt-4 text-sm text-gray-500">Available for payments and transfers</div>
          <div class="mt-4 flex gap-2">
            <button class="px-3 py-2 rounded-lg bg-green-600 text-white">Receive</button>
            <button id="sendMoneyBtn2" class="px-3 py-2 rounded-lg bg-red-600 text-white">Send</button>
          </div>
        </div>
      </section>

      <section class="mt-6 grid grid-cols-2 gap-6">
        <div class="bg-white p-5 rounded-2xl shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm text-gray-500">Recent Transactions</div>
              <div class="font-semibold">Activity</div>
            </div>
            <div class="text-sm text-indigo-600 cursor-pointer">View all</div>
          </div>

          <ul class="mt-4 divide-y">
            <?php foreach ($transactions as $tx): ?>
              <li class="py-3">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="font-medium"><?php echo htmlspecialchars($tx['description'] ?: ($tx['type'])); ?></div>
                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($tx['created_at']); ?> • <?php echo htmlspecialchars($tx['type']); ?><?php echo !empty($tx['provider']) ? ' • ' . htmlspecialchars($tx['provider']) : ''; ?></div>
                    <?php if (!empty($tx['customer_ref'])): ?><div class="text-xs text-gray-400">Customer: <?php echo htmlspecialchars($tx['customer_ref']); ?></div><?php endif; ?>
                    <?php if (!empty($tx['txn_id'])): ?><div class="text-xs text-gray-400">Txn: <a href="receipt.php?txn=<?php echo urlencode($tx['txn_id']); ?>" class="text-indigo-600"><?php echo htmlspecialchars($tx['txn_id']); ?></a></div><?php endif; ?>
                  </div>
                  <div class="font-semibold <?php echo ($tx['amount'] < 0) ? 'text-red-500' : 'text-green-600'; ?>">
                    <?php echo 'Rs ' . number_format(abs($tx['amount']), 2); ?>
                  </div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm">
          <div class="text-sm text-gray-500">Quick Send To</div>
          <div class="mt-4 grid grid-cols-3 gap-3">
            <?php foreach (['Ram', 'Sita', 'Shop', 'Bank', 'Mom', 'Dad'] as $contact): ?>
              <button class="p-3 bg-gray-50 rounded-lg text-sm"><?php echo htmlspecialchars($contact); ?></button>
            <?php endforeach; ?>
          </div>

          <div class="mt-6 text-sm text-gray-500">Offers & Rewards</div>
          <div class="mt-3 p-3 bg-indigo-50 rounded-lg">Get 5% cashback on select bill payments.</div>
        </div>
      </section>

      <footer class="mt-6 text-center text-xs text-gray-400">e-sewa clone UI — demo • Not affiliated with e-sewa</footer>
    </main>
  </div>

  <!-- Transfer modal -->
  <div id="transferModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
      <h2 class="text-lg font-semibold">Send Money</h2>
      <form id="sendForm" class="mt-4 space-y-3">
        <div>
          <label class="text-xs text-gray-500" for="to">To (e-sewa ID / Bank)</label>
          <input id="to" name="to" class="w-full mt-1 px-3 py-2 border rounded" autocomplete="off" />
        </div>
        <div>
          <label class="text-xs text-gray-500" for="amount">Amount (Rs)</label>
          <input id="amount" name="amount" type="number" step="0.01" class="w-full mt-1 px-3 py-2 border rounded" autocomplete="off" />
        </div>
        <div>
          <label class="text-xs text-gray-500" for="note">Note (optional)</label>
          <input id="note" name="note" class="w-full mt-1 px-3 py-2 border rounded" autocomplete="off" />
        </div>

        <div class="flex justify-end gap-2">
          <button id="cancelBtn" type="button" class="px-4 py-2 rounded border">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white">Send</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
