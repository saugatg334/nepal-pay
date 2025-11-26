# NepalPay Wallet — Fintech Demo

A demo **NepalPay** wallet application built with PHP, MySQL, and Tailwind CSS. Includes user authentication, bill payments, wallet transfers, and mock provider integration (NEA, water, ISP).

## Features

- **User Auth**: Registration, login with phone & password, session management
- **Wallet**: Check balance, deposit, send money, transaction history
- **Bills & Utilities**: Pay bills via NepalPay or bank transfer
- **Providers**: Mock NEA, water, and ISP endpoints for demo
- **Receipts**: Detailed transaction receipts with provider metadata
- **UI**: Professional dashboard and modern login/register pages (Tailwind CSS)

## Project Structure

```
wallet/
├── app/
│   ├── config/database.php          # DB connection (PDO)
│   ├── controller/
│   │   ├── AuthController.php       # Login/Register logic
│   │   ├── WalletController.php     # Wallet operations
│   │   └── BillController.php       # Bill payment logic
│   ├── models/
│   │   ├── User.php                 # User model with schema-aware inserts
│   │   └── Provider.php             # Provider/biller model
│   ├── helper/APIClient.php         # API calls (NEA, mock providers)
│   └── helpers/session_helper.php   # Session & flash message utilities
├── public/
│   ├── dashboard.php                # Main wallet UI
│   ├── login.php / register.php      # Auth UI
│   ├── deposit.php                  # Deposit form
│   ├── pay.php                      # Bill payment UI
│   ├── receipt.php                  # Transaction receipt
│   ├── profile.php                  # User profile
│   ├── api/
│   │   ├── get_bill.php             # NEA bill lookup proxy
│   │   └── provider_callback.php    # Provider callback simulator
│   ├── mock/
│   │   ├── nea.php                  # Mock NEA provider
│   │   ├── water.php                # Mock water provider
│   │   └── isp.php                  # Mock ISP provider
│   └── assets/
│       ├── style.css                # Custom styles
│       └── logo.svg                 # NepalPay logo
├── database/
│   ├── create_*.sql                 # Schema files
│   ├── migrations/
│   │   └── 001_add_txn_fields.sql   # Add transaction metadata columns
│   └── run_migrations.php           # Migration runner
└── logs/
    └── deposits.log                 # Deposit audit log
```

## Getting Started

### Prerequisites
- **XAMPP** (Apache + MySQL + PHP 7.4+)
- **Git**

### Setup

1. **Clone or extract the wallet folder**:
```bash
cd c:\xampp1\htdocs\wallet
```

2. **Create the database**:
```bash
"C:\xampp1\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS nepal_pay_simple CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

3. **Import schema and migrations**:
```bash
cd c:\xampp1\htdocs\wallet\database
"C:\xampp1\mysql\bin\mysql.exe" -u root nepal_pay_simple < create_users_table.sql
"C:\xampp1\mysql\bin\mysql.exe" -u root nepal_pay_simple < create_transactions_table.sql
"C:\xampp1\mysql\bin\mysql.exe" -u root nepal_pay_simple < migrations\001_add_txn_fields.sql
```

4. **Start Apache & MySQL** in XAMPP Control Panel.

5. **Access the app**:
```
http://localhost/wallet/public/
```

### Test Accounts

After DB setup, create a test user via the Register page, or insert directly:
```sql
INSERT INTO users (name, phone, password, wallet_balance) 
VALUES ('Test User', '9841234567', PASSWORD('pass123'), 50000);
```

## Usage

### Login & Wallet
- Register or login with phone & password.
- Check wallet balance and transaction history on the dashboard.

### Send Money
- Click "Send Money" → enter recipient phone and amount → confirm.

### Bill Payment
- Click "Pay Bill" → select biller (NEA, Water, ISP) → enter amount & reference → choose payment method → confirm.
- **SC No (NEA)**: Lookup is mocked; try any 8-digit number.

### Deposit
- Click "Deposit" → enter amount → submit.
- Logs are written to `logs/deposits.log`.

### Provider Callbacks (Testing)
To simulate a provider confirmation:
```bash
curl -X POST http://localhost/wallet/public/api/provider_callback.php \
  -H "Content-Type: application/json" \
  -d '{"txn_id":"NP1234567890","status":"confirmed"}'
```

## Integration with Real Providers

### NEA
Replace in `app/helper/APIClient.php`:
- `$this->baseUrl` and `$this->apiKey` with real NEA credentials.
- The `getNEABill()` method already calls the real NEA endpoint; switch from mock in `public/api/get_bill.php` if needed.

### Water, ISP, Other Billers
- Implement provider classes inheriting from a base `Provider` interface.
- Update `BillController::payBill()` to route to provider-specific payment handlers.
- Store provider credentials in environment variables (`.env`).

## Database Schema

### users
- `id`, `name`, `phone`, `password`, `wallet_balance`, `pin`, `kyc_status`, `kyc_documents`, `last_login`, `failed_login_attempts`, `account_locked_until`, `device_token`

### transactions
- `id`, `sender_id`, `receiver_id`, `amount`, `type` (enum: deposit, transfer, withdrawal, bill_payment, bank_payment), `status` (enum: pending, completed, failed)
- **New columns** (added via migration): `txn_id`, `provider`, `customer_ref`, `method`, `provider_response`

### providers & billers
- See `database/create_providers_table.sql` for structure.

## Notes

- **Session**: Uses PHP sessions; session state stored server-side.
- **Password Hashing**: `password_hash()` with PHP's default algorithm (BCRYPT).
- **Schema Adaptation**: `User::recordTransaction()` auto-detects available columns; older DBs without new fields will still work.
- **Logging**: Deposits are logged to `logs/deposits.log` with timestamps and status.
- **Mock Providers**: Return simulated JSON responses. Replace with real API calls in production.

## Security

⚠️ **For Demo Only**
- No CSRF tokens (add in production).
- Plaintext API keys in config (use `.env` in production).
- No rate limiting or input sanitization (add validation layer).
- Database password empty in local dev (change in production).

## Future Enhancements

- [ ] Admin dashboard for provider management
- [ ] Transaction filtering and export
- [ ] Multi-language support
- [ ] Mobile app (React Native / Flutter)
- [ ] Payment gateway integration (Khalti, Esewa, IME Pay)
- [ ] Real provider APIs (NEA, NTC, FECL)
- [ ] Webhook handling and async confirmations
- [ ] Unit & integration tests

## Contributing

Contributions welcome! Please fork and submit pull requests.

## License

MIT License — See LICENSE file for details.

## Contact

For questions or feedback, contact the development team or open an issue on GitHub.
