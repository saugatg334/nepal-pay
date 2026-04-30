<?php
/**
 * Route Handler — NepalPay Unified Router
 * 
 * Consolidates legacy Router with Controller-based routing.
 * All requests flow through public/index.php which bootstraps
 * NepalPay\Core\App and routes via this class.
 * 
 * NOTE: The redirect() wrapper is guarded with function_exists()
 *       because helpers.php defines it first via Composer autoload.
 */

class Router
{
    private $userModel;
    private $walletModel;
    private $transactionModel;
    private $billModel;

    public function __construct()
    {
        require_once BASE_PATH . '/app/models/UserModel.php';
        require_once BASE_PATH . '/app/models/WalletModel.php';
        require_once BASE_PATH . '/app/models/TransactionModel.php';
        require_once BASE_PATH . '/app/models/BillPaymentModel.php';

        $this->userModel    = new UserModel();
        $this->walletModel  = new WalletModel();
        $this->transactionModel = new TransactionModel();
        $this->billModel    = new BillPaymentModel();
    }

    /**
     * Route the request
     */
    public function route(): void
    {
        $page   = $_GET['page'] ?? 'index';
        $action = $_GET['action'] ?? null;

        if ($action === 'logout') {
            $this->handleLogout();
            return;
        }

        $protectedPages = [
            'dashboard', 'send', 'add', 'withdraw',
            'bill', 'transactions', 'profile', 'settings', 'cards'
        ];

        if (in_array($page, $protectedPages, true) && !Session::has('user_id')) {
            redirect(APP_URL . '/index.php?page=login');
            return;
        }

        switch ($page) {
            case 'index':
                $this->showAuthPage();
                break;
            case 'dashboard':
                $this->showDashboard();
                break;
            case 'send':
                $this->showSendMoney();
                break;
            case 'add':
                $this->showAddMoney();
                break;
            case 'withdraw':
                $this->showWithdraw();
                break;
            case 'bill':
                $this->showBillPayment();
                break;
            case 'transactions':
                $this->showTransactions();
                break;
            case 'profile':
                $this->showProfile();
                break;
            case 'settings':
                $this->showSettings();
                break;
            default:
                $this->showAuthPage();
                break;
        }
    }

    /* -------------------------------------------------
       VIEW RENDERERS
       ------------------------------------------------- */

    private function showAuthPage(): void
    {
        if (Session::has('user_id')) {
            redirect(APP_URL . '/index.php?page=dashboard');
            return;
        }
        $this->renderWithLayout('auth', [
            'pageTitle'   => 'NepalPay — Login',
            'currentPage' => 'auth'
        ]);
    }

    private function showDashboard(): void
    {
        $userId = (int) Session::get('user_id');
        $user   = $this->userModel->findById($userId);
        $wallet = $this->walletModel->getByUserId($userId);

        $stats              = $this->transactionModel->getDashboardStats($userId);
        $monthlyData        = $this->transactionModel->getMonthlySummary($userId);
        $recentTransactions = $this->transactionModel->getUserTransactions($userId, 5);

        $this->renderWithLayout('dashboard', [
            'pageTitle'          => 'Dashboard',
            'currentPage'        => 'dashboard',
            'userName'           => $user['name'] ?? 'User',
            'walletBalance'      => $wallet['balance'] ?? 0,
            'stats'              => $stats,
            'monthlyData'        => $monthlyData,
            'recentTransactions' => $recentTransactions
        ]);
    }

    private function showSendMoney(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSendMoney();
            return;
        }
        $this->renderWithLayout('send-money', [
            'pageTitle'   => 'Send Money',
            'currentPage' => 'send'
        ]);
    }

    private function showAddMoney(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAddMoney();
            return;
        }
        $this->renderWithLayout('add-money', [
            'pageTitle'   => 'Add Money',
            'currentPage' => 'add'
        ]);
    }

    private function showWithdraw(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleWithdraw();
            return;
        }
        $this->renderWithLayout('withdraw', [
            'pageTitle'   => 'Withdraw',
            'currentPage' => 'withdraw'
        ]);
    }

    private function showBillPayment(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleBillPayment();
            return;
        }
        $providers = BillPaymentModel::getProviders();
        $this->renderWithLayout('bill-payment', [
            'pageTitle'   => 'Bill Payment',
            'currentPage' => 'bill',
            'providers'   => $providers
        ]);
    }

    private function showTransactions(): void
    {
        $userId = (int) Session::get('user_id');
        $page   = max(1, (int) ($_GET['p'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $transactions = $this->transactionModel->getUserTransactions($userId, $limit, $offset);
        $totalCount   = $this->transactionModel->getUserTransactionCount($userId);
        $totalPages   = max(1, (int) ceil($totalCount / $limit));

        $this->renderWithLayout('transactions', [
            'pageTitle'    => 'Transactions',
            'currentPage'  => 'transactions',
            'transactions' => $transactions,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'totalCount'   => $totalCount
        ]);
    }

    private function showProfile(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleProfileUpdate();
            return;
        }
        $userId = (int) Session::get('user_id');
        $user   = $this->userModel->findById($userId);
        $this->renderWithLayout('profile', [
            'pageTitle'   => 'My Profile',
            'currentPage' => 'profile',
            'user'        => $user
        ]);
    }

    private function showSettings(): void
    {
        $this->renderWithLayout('settings', [
            'pageTitle'   => 'Settings',
            'currentPage' => 'settings'
        ]);
    }

    /* -------------------------------------------------
       ACTION HANDLERS
       ------------------------------------------------- */

    private function handleSendMoney(): void
    {
        $userId         = (int) Session::get('user_id');
        $recipientPhone = sanitize($_POST['recipient_phone'] ?? '');
        $amount         = floatval($_POST['amount'] ?? 0);
        $description    = sanitize($_POST['description'] ?? '');

        if (!$recipientPhone || $amount <= 0) {
            flash('error', 'Invalid recipient or amount');
            redirect(APP_URL . '/index.php?page=send');
            return;
        }

        $recipient = $this->userModel->findByPhone($recipientPhone);
        if (!$recipient) {
            flash('error', 'Recipient not found');
            redirect(APP_URL . '/index.php?page=send');
            return;
        }

        // Prevent self-transfer
        if ((int) $recipient['id'] === $userId) {
            flash('error', 'You cannot send money to yourself');
            redirect(APP_URL . '/index.php?page=send');
            return;
        }

        $senderWallet = $this->walletModel->getByUserId($userId);
        if (!$senderWallet || (float) $senderWallet['balance'] < $amount) {
            flash('error', 'Insufficient balance');
            redirect(APP_URL . '/index.php?page=send');
            return;
        }

        Database::beginTransaction();
        try {
            // Re-fetch with row lock inside transaction
            $senderWalletLocked   = Database::fetch(
                "SELECT * FROM wallets WHERE user_id = ? AND is_active = 1 FOR UPDATE",
                [$userId]
            );
            $recipientWalletLocked = Database::fetch(
                "SELECT * FROM wallets WHERE user_id = ? AND is_active = 1 FOR UPDATE",
                [$recipient['id']]
            );

            if (!$senderWalletLocked || (float) $senderWalletLocked['balance'] < $amount) {
                throw new RuntimeException('Insufficient balance');
            }
            if (!$recipientWalletLocked) {
                throw new RuntimeException('Recipient wallet not available');
            }

            // Atomic debit + credit
            Database::query("UPDATE wallets SET balance = balance - ? WHERE id = ?", [$amount, $senderWalletLocked['id']]);
            Database::query("UPDATE wallets SET balance = balance + ? WHERE id = ?", [$amount, $recipientWalletLocked['id']]);

            // Single transaction record (no duplicate)
            $txnId = generateTransactionId();
            Database::query("
                INSERT INTO transactions 
                (transaction_id, sender_id, receiver_id, amount, type, status, description, created_at)
                VALUES (?, ?, ?, ?, 'send', 'completed', ?, NOW())
            ", [$txnId, $userId, $recipient['id'], $amount, $description ?: 'Sent to ' . $recipient['name']]);

            // Audit log
            Database::query("
                INSERT INTO balance_adjustments 
                (user_id, wallet_id, adjustment_type, amount, balance_before, balance_after, reason, reference_id, adjusted_by, created_at)
                VALUES (?, ?, 'debit', ?, ?, ?, 'money_sent', ?, ?, NOW())
            ", [$userId, $senderWalletLocked['id'], $amount, $senderWalletLocked['balance'], $senderWalletLocked['balance'] - $amount, $txnId, $userId]);

            Database::query("
                INSERT INTO balance_adjustments 
                (user_id, wallet_id, adjustment_type, amount, balance_before, balance_after, reason, reference_id, adjusted_by, created_at)
                VALUES (?, ?, 'credit', ?, ?, ?, 'money_received', ?, ?, NOW())
            ", [$recipient['id'], $recipientWalletLocked['id'], $amount, $recipientWalletLocked['balance'], $recipientWalletLocked['balance'] + $amount, $txnId, $userId]);

            Database::commit();
            flash('success', 'Money sent successfully!');
        } catch (Throwable $e) {
            Database::rollBack();
            flash('error', 'Transfer failed: ' . $e->getMessage());
        }

        redirect(APP_URL . '/index.php?page=dashboard');
    }

    private function handleAddMoney(): void
    {
        $userId = (int) Session::get('user_id');
        $amount = floatval($_POST['amount'] ?? 0);

        if ($amount <= 0) {
            flash('error', 'Invalid amount');
            redirect(APP_URL . '/index.php?page=add');
            return;
        }

        Database::beginTransaction();
        try {
            $wallet = Database::fetch(
                "SELECT * FROM wallets WHERE user_id = ? AND is_active = 1 FOR UPDATE",
                [$userId]
            );
            if (!$wallet) {
                throw new RuntimeException('Wallet not found');
            }

            $before = (float) $wallet['balance'];
            Database::query("UPDATE wallets SET balance = balance + ? WHERE id = ?", [$amount, $wallet['id']]);

            $txnId = generateTransactionId();
            Database::query("
                INSERT INTO transactions 
                (transaction_id, receiver_id, amount, type, status, description, created_at)
                VALUES (?, ?, ?, 'add_money', 'completed', ?, NOW())
            ", [$txnId, $userId, $amount, 'Added money to wallet']);

            Database::query("
                INSERT INTO balance_adjustments 
                (user_id, wallet_id, adjustment_type, amount, balance_before, balance_after, reason, reference_id, adjusted_by, created_at)
                VALUES (?, ?, 'credit', ?, ?, ?, 'wallet_topup', ?, ?, NOW())
            ", [$userId, $wallet['id'], $amount, $before, $before + $amount, $txnId, null]);

            Database::commit();
            flash('success', 'Money added successfully!');
        } catch (Throwable $e) {
            Database::rollBack();
            flash('error', 'Failed to add money: ' . $e->getMessage());
        }

        redirect(APP_URL . '/index.php?page=dashboard');
    }

    private function handleWithdraw(): void
    {
        $userId = (int) Session::get('user_id');
        $amount = floatval($_POST['amount'] ?? 0);

        if ($amount <= 0) {
            flash('error', 'Invalid amount');
            redirect(APP_URL . '/index.php?page=withdraw');
            return;
        }

        Database::beginTransaction();
        try {
            $wallet = Database::fetch(
                "SELECT * FROM wallets WHERE user_id = ? AND is_active = 1 FOR UPDATE",
                [$userId]
            );
            if (!$wallet || (float) $wallet['balance'] < $amount) {
                throw new RuntimeException('Insufficient balance');
            }

            $before = (float) $wallet['balance'];
            Database::query("UPDATE wallets SET balance = balance - ? WHERE id = ?", [$amount, $wallet['id']]);

            $txnId = generateTransactionId();
            Database::query("
                INSERT INTO transactions 
                (transaction_id, sender_id, amount, type, status, description, created_at)
                VALUES (?, ?, ?, 'withdraw', 'completed', ?, NOW())
            ", [$txnId, $userId, $amount, 'Withdrawn from wallet']);

            Database::query("
                INSERT INTO balance_adjustments 
                (user_id, wallet_id, adjustment_type, amount, balance_before, balance_after, reason, reference_id, adjusted_by, created_at)
                VALUES (?, ?, 'debit', ?, ?, ?, 'withdrawal', ?, ?, NOW())
            ", [$userId, $wallet['id'], $amount, $before, $before - $amount, $txnId, null]);

            Database::commit();
            flash('success', 'Withdrawal successful!');
        } catch (Throwable $e) {
            Database::rollBack();
            flash('error', 'Withdrawal failed: ' . $e->getMessage());
        }

        redirect(APP_URL . '/index.php?page=dashboard');
    }

    private function handleBillPayment(): void
    {
        $userId     = (int) Session::get('user_id');
        $provider   = sanitize($_POST['provider'] ?? '');
        $category   = sanitize($_POST['category'] ?? '');
        $customerId = sanitize($_POST['customer_id'] ?? '');
        $amount     = floatval($_POST['amount'] ?? 0);

        if (!$provider || !$category || !$customerId || $amount <= 0) {
            flash('error', 'All fields are required');
            redirect(APP_URL . '/index.php?page=bill');
            return;
        }

        Database::beginTransaction();
        try {
            $wallet = Database::fetch(
                "SELECT * FROM wallets WHERE user_id = ? AND is_active = 1 FOR UPDATE",
                [$userId]
            );
            if (!$wallet || (float) $wallet['balance'] < $amount) {
                throw new RuntimeException('Insufficient balance');
            }

            $before = (float) $wallet['balance'];
            Database::query("UPDATE wallets SET balance = balance - ? WHERE id = ?", [$amount, $wallet['id']]);

            $txnId = generateTransactionId();
            $desc  = ucfirst($category) . ' bill paid to ' . $provider;

            Database::query("
                INSERT INTO transactions 
                (transaction_id, sender_id, amount, type, status, description, created_at)
                VALUES (?, ?, ?, 'bill_payment', 'completed', ?, NOW())
            ", [$txnId, $userId, $amount, $desc]);

            Database::query("
                INSERT INTO bill_payments 
                (user_id, bill_type, bill_number, amount, status, transaction_id, created_at)
                VALUES (?, ?, ?, ?, 'completed', ?, NOW())
            ", [$userId, $category, $customerId, $amount, $txnId]);

            Database::query("
                INSERT INTO balance_adjustments 
                (user_id, wallet_id, adjustment_type, amount, balance_before, balance_after, reason, reference_id, adjusted_by, created_at)
                VALUES (?, ?, 'debit', ?, ?, ?, 'bill_payment', ?, ?, NOW())
            ", [$userId, $wallet['id'], $amount, $before, $before - $amount, $txnId, $userId]);

            Database::commit();
            flash('success', 'Bill payment successful!');
        } catch (Throwable $e) {
            Database::rollBack();
            flash('error', 'Payment failed: ' . $e->getMessage());
        }

        redirect(APP_URL . '/index.php?page=dashboard');
    }

    private function handleProfileUpdate(): void
    {
        $userId  = (int) Session::get('user_id');
        $name    = sanitize($_POST['name'] ?? '');
        $phone   = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        if (!$name || !$phone) {
            flash('error', 'Name and phone are required');
            redirect(APP_URL . '/index.php?page=profile');
            return;
        }

        $this->userModel->update($userId, [
            'name'    => $name,
            'phone'   => $phone,
            'address' => $address
        ]);

        Session::set('user_name', $name);
        Session::set('user_phone', $phone);

        flash('success', 'Profile updated successfully!');
        redirect(APP_URL . '/index.php?page=profile');
    }

    private function handleLogout(): void
    {
        Session::destroy();
        flash('success', 'Logged out successfully.');
        redirect(APP_URL . '/index.php?page=index');
    }

    /* -------------------------------------------------
       RENDER UTILITIES
       ------------------------------------------------- */

    /**
     * Render view wrapped in layout
     */
    private function renderWithLayout(string $view, array $data = []): void
    {
        extract($data);
        $viewFile   = BASE_PATH . '/app/views/' . $view . '.php';
        $layoutFile = BASE_PATH . '/app/views/layout.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo '<h1>404 — View not found</h1>';
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }
}

/* ==================================================
   Redirect helper guard
   helpers.php loads first via Composer; this prevents
   the fatal "Cannot redeclare" error.
   ================================================== */
if (!function_exists('redirect')) {
    function redirect(string $url): void
    {
        session_write_close();
        header('Location: ' . $url);
        exit;
    }
}
