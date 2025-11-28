// Minimal frontend helper for NepalPay demo
document.addEventListener('DOMContentLoaded', function(){
  // Sidebar toggle (if present)
  var sidebarToggle = document.getElementById('sidebarToggle');
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function(){
      document.querySelector('aside').classList.toggle('hidden');
    });
  }

  // Generic modal openers for elements with data-modal attribute
  document.querySelectorAll('[data-modal-target]').forEach(function(btn){
    btn.addEventListener('click', function(e){
      var id = btn.getAttribute('data-modal-target');
      var modal = document.getElementById(id);
      if (modal) modal.classList.remove('hidden');
    });
  });

  document.querySelectorAll('.modal-close').forEach(function(btn){
    btn.addEventListener('click', function(){
      var modal = btn.closest('.modal');
      if (modal) modal.classList.add('hidden');
    });
  });
});
// public/assets/js/app.js
document.addEventListener('DOMContentLoaded', () => {
  const transferModal = document.getElementById('transferModal');
  const openSend = document.getElementById('openSend');
  const sendNowBtn = document.getElementById('sendNowBtn');
  const sendMoneyQuick = document.getElementById('sendMoneyQuick');
  const cancelBtn = document.getElementById('cancelBtn');
  const sendForm = document.getElementById('sendForm');
  const walletBalanceWidget = document.getElementById('walletBalanceWidget');
  const walletBalanceSidebar = document.getElementById('walletBalanceSidebar');
  const txSearch = document.getElementById('txSearch');
  const txList = document.getElementById('txList');

  let currentBalance = parseFloat(window.__NEPALPAY && window.__NEPALPAY.balance) || 0.00;

  function formatRs(amount) {
    return 'Rs ' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function updateBalanceUI() {
    [walletBalanceWidget, walletBalanceSidebar].forEach(el => {
      if (el) el.textContent = formatRs(currentBalance);
    });
  }

  // Open modal handlers
  [openSend, sendNowBtn, sendMoneyQuick].forEach(btn => {
    if (!btn) return;
    btn.addEventListener('click', () => transferModal.classList.remove('hidden'));
  });

  cancelBtn && cancelBtn.addEventListener('click', () => {
    transferModal.classList.add('hidden');
    sendForm.reset();
  });

  // Quick contact buttons populate "to"
  document.querySelectorAll('.quick-contact').forEach(b => {
    b.addEventListener('click', () => {
      const to = document.getElementById('to');
      if (to) to.value = b.textContent.trim();
      transferModal.classList.remove('hidden');
    });
  });

  // Form submit (frontend demo)
  sendForm && sendForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const to = sendForm.to.value.trim();
    const amount = parseFloat(sendForm.amount.value);
    if (!to || !amount || amount <= 0 || amount > currentBalance) {
      alert('Please enter valid details (amount must be positive and ≤ balance).');
      return;
    }
    currentBalance = +(currentBalance - amount).toFixed(2);
    updateBalanceUI();
    // Append to txList locally (demo)
    if (txList) {
      const li = document.createElement('li');
      li.className = 'py-3';
      li.innerHTML = `
        <div class="flex items-center justify-between">
          <div>
            <div class="font-medium">Sent to ${to}</div>
            <div class="text-xs text-gray-400">${new Date().toLocaleString()} • Sent</div>
          </div>
          <div class="font-semibold text-red-500">${formatRs(amount)}</div>
        </div>
      `;
      txList.prepend(li);
    }
    transferModal.classList.add('hidden');
    sendForm.reset();
  });

  // Search transactions (simple client-side filter)
  txSearch && txSearch.addEventListener('input', (e) => {
    const q = e.target.value.toLowerCase();
    if (!txList) return;
    txList.querySelectorAll('li').forEach(li => {
      const txt = li.textContent.toLowerCase();
      li.style.display = txt.includes(q) ? '' : 'none';
    });
  });

  // Init UI
  updateBalanceUI();
});
