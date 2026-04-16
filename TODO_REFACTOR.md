# NepalPay Enterprise Refactor

## Phase 1: Cleanup (Immediate)
- [x] Delete RateLimiter.php dupes (app/services, app/middleware - confirmed gone)
- [x] Delete public/user/ and public/admin/ legacy (12+4 files deleted)
- [ ] Add routes for deleted pages

## Phase 2: DB Fixes
- [x] New migration 010_indexes.sql (transactions, ledger_entries, audit_logs indexed)
- [ ] Drop wallet_ledger if exists

## Phase 3: Model Split
- [x] app/models/User.php → Infrastructure/Database/Repositories/UserRepository.php (queries only)
- [x] Create AuthService, WalletRepository, LedgerRepository (Phase 3b)

## Phase 4: Arch Restructure
- [ ] Create app/Http/Controllers, Domain/Wallet, Infrastructure/Database
- [ ] Move files
- [ ] DI container simple

## Phase 5: Controllers update
- [ ] Inject services
- [ ] Use repositories

## Phase 6: Security
- [ ] CSRF middleware all POST

## Phase 7: Tests
- [ ] Fix validate_system.php deps
- [ ] Add concurrency test

Progress: 0/50

