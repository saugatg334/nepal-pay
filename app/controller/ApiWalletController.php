    public function send(): void {
        $this->authenticate();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Method not allowed', 405);
        }
        
        $recipientWallet = Input::string('wallet_number', '');
        $amount = Input::float('amount', 0.0);
        $description = Input::string('description', '');
        $pin = Input::string('transaction_pin', '');
        
        // CSRF protection for API state changes (check header)
        $csrfToken = Input::string('csrf_token', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!\NepalPay\Helpers\CSRF::validateToken($csrfToken)) {
            $this->jsonError('CSRF token validation failed', 403);
        }
        
        if (empty($recipientWallet) || $amount <= 0) {
            $this->jsonError('Valid amount and recipient required', 400);
        }
        
        // Validate recipient exists
        $recipient = Wallet::findByNumber($recipientWallet);
        if (!$recipient) {
            $this->jsonError('Recipient wallet not found', 404);
        }
        
        // Can't send to self
        if ($recipient['user_id'] == $this->getUserId()) {
            $this->jsonError('Cannot send to yourself', 400);
        }
        
        $pinData = $pin ? ['pin' => $pin] : null;
        
        // Perform transaction
        try {
            if (class_exists('\\NepalPay\\Services\\WalletService')) {
                $result = \NepalPay\Services\WalletService::sendMoney(
                    $this->getUserId(),
                    $recipientWallet,
                    $amount,
                    $description,
                    $pinData
                );
                $this->jsonSuccess($result, 'Money sent successfully');
                return;
            }
            
            // Fallback to inline implementation
            // Check PIN if required
            if (class_exists('\\NepalPay\\Services\\TransactionPinService') && 
                \NepalPay\Services\TransactionPinService::isRequired($this->getUserId())) {
                if (!$pinData || !isset($pinData['pin'])) {
                    $this->jsonError('Transaction PIN required', 400);
                }
                if (!\NepalPay\Services\TransactionPinService::verify($this->getUserId(), $pinData['pin'])) {
                    $remaining = \NepalPay\Services\TransactionPinService::remainingAttempts($this->getUserId());
                    $this->jsonError('Invalid PIN. ' . $remaining . ' attempts remaining.', 403);
                }
            }
            
            Database::beginTransaction();
            
            $senderWallet = Wallet::findByUserId($this->getUserId());
            if (!$senderWallet) {
                throw new Exception('Sender wallet not found');
            }
            
            if ($senderWallet['balance'] < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            $newSenderBalance = $senderWallet['balance'] - $amount;
            $sql = "UPDATE wallets SET balance = ? WHERE id = ?";
            Database::query($sql, [$newSenderBalance, $senderWallet['id']]);
            
            $newRecipientBalance = $recipient['balance'] + $amount;
            $sql = "UPDATE wallets SET balance = ? WHERE id = ?";
            Database::query($sql, [$newRecipientBalance, $recipient['id']]);
            
            $transactionId = 'TXN' . time() . random_int(1000, 9999);
            $sql = "INSERT INTO transactions 
                    (transaction_id, sender_id, receiver_id, amount, type, status, description)
                    VALUES (?, ?, ?, ?, 'send', 'completed', ?)";
            Database::query($sql, [
                $transactionId,
                $this->getUserId(),
                $recipient['user_id'],
                $amount,
                $description
            ]);
            
            Database::commit();
            
            $this->jsonSuccess([
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'recipient_wallet' => $recipientWallet,
                'new_balance' => $newSenderBalance
            ], 'Money sent successfully');
            
        } catch (Exception $e) {
            if (Database::rollBack()) {
                // Rollback was active and succeeded
            }
            \NepalPay\Core\Logger::error('Wallet send failed', [
                'user_id' => $this->getUserId(),
                'error' => $e->getMessage()
            ]);
            $this->jsonError('Transaction failed: ' . $e->getMessage(), 500);
        }
    }