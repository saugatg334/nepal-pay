<?php
/**
 * NepalPay Routes
 * Maps URLs to Controller/Method
 * 
 * Format: 'route' => [Controller, Method]
 * 
 * Auth routes (guest only)
 * User routes (requires login)
 * Admin routes (requires admin login)
 */

return [
    // ============ GUEST ROUTES ============
    'GET /' => ['AuthController', 'showLogin'],
    'GET /login' => ['AuthController', 'showLogin'],
    'POST /login' => ['AuthController', 'login'],
    'GET /register' => ['AuthController', 'showRegister'],
    'POST /register' => ['AuthController', 'register'],
    'GET /logout' => ['AuthController', 'logout'],
    'GET /forgot-password' => ['AuthController', 'showForgotPassword'],
    'POST /forgot-password' => ['AuthController', 'sendResetLink'],
    'GET /admin/login' => ['AuthController', 'showAdminLogin'],
    'POST /admin/login' => ['AuthController', 'adminLogin'],
    
    // ============ USER ROUTES ============
    'GET /dashboard' => ['UserController', 'dashboard'],
    'GET /wallet' => ['UserController', 'wallet'],
    'POST /wallet/deposit' => ['UserController', 'deposit'],
    'GET /send-money' => ['UserController', 'showSendMoney'],
    'POST /send-money' => ['UserController', 'sendMoney'],
    'GET /request-money' => ['UserController', 'showRequestMoney'],
    'POST /request-money' => ['UserController', 'requestMoney'],
    'GET /topup' => ['TopupController', 'showTopup'],
    'POST /topup' => ['TopupController', 'processTopup'],
    'GET /pay-bills' => ['PayBillsController', 'showBillTypes'],
    'GET /pay-bills/fetch' => ['PayBillsController', 'fetchCustomer'],
    'POST /pay-bills/confirm' => ['PayBillsController', 'confirmPayment'],
    'POST /pay-bills/process' => ['PayBillsController', 'processPayment'],
    'GET /transactions' => ['UserController', 'transactions'],
    'GET /profile' => ['UserController', 'profile'],
    'POST /profile' => ['UserController', 'updateProfile'],
    'GET /kyc' => ['UserController', 'kyc'],
    'POST /kyc' => ['UserController', 'submitKYC'],
    'GET /change-password' => ['UserController', 'showChangePassword'],
    'POST /change-password' => ['UserController', 'changePassword'],
    'GET /notifications' => ['UserController', 'notifications'],
    'GET /set-pin' => ['UserController', 'showSetPin'],
    'POST /set-pin' => ['UserController', 'setPin'],
    
    // ============ ADMIN ROUTES ============
    'GET /admin/dashboard' => ['AdminController', 'dashboard'],
    'GET /admin/users' => ['AdminController', 'users'],
    'POST /admin/users/activate' => ['AdminController', 'activateUser'],
    'POST /admin/users/deactivate' => ['AdminController', 'deactivateUser'],
    'GET /admin/transactions' => ['AdminController', 'transactions'],
    'GET /admin/kyc-verification' => ['AdminController', 'kycVerification'],
    'POST /admin/kyc/approve' => ['AdminController', 'approveKYC'],
    'POST /admin/kyc/reject' => ['AdminController', 'rejectKYC'],
    'GET /admin/wallet-management' => ['AdminController', 'walletManagement'],
    'GET /admin/deposits' => ['AdminController', 'deposits'],
    'GET /admin/withdrawals' => ['AdminController', 'withdrawals'],
    'GET /admin/reports' => ['AdminController', 'reports'],
    'GET /admin/settings' => ['AdminController', 'settings'],
    'POST /admin/settings' => ['AdminController', 'updateSettings'],
    'GET /admin/profile' => ['AdminController', 'profile'],
];
