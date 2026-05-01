<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');

require_once __DIR__ . '/../app/bootstrap.php';

set_exception_handler(function (Throwable $e) {
    error_log("UNCAUGHT EXCEPTION: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(500);
    $isDebug = ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1');

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Error - NepalPay</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">';
    echo '</head><body class="bg-light">';
    echo '<div class="container mt-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-6">';
    echo '<div class="card border-danger shadow"><div class="card-header bg-danger text-white">';
    echo '<h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>An Error Occurred</h5></div>';
    echo '<div class="card-body">';
    echo '<p class="lead">We\'re sorry, but something went wrong.</p>';

    if ($isDebug || !empty($_GET['debug'])) {
        echo '<div class="mb-3"><div class="alert alert-warning">';
        echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }

    $loginUrl = (defined('APP_URL') ? APP_URL : 'http://localhost/wallet');
    echo '<a href="' . $loginUrl . '/index.php?page=login" class="btn btn-primary mt-3">Return to Login</a>';
    echo '</div></div></div></div></div></body></html>';
    exit;
});

set_error_handler(function (int $severity, string $message, string $file, int $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code(500);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Fatal Error - NepalPay</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">';
        echo '</head><body class="bg-light">';
        echo '<div class="container mt-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-6">';
        echo '<div class="card border-danger shadow"><div class="card-header bg-danger text-white">';
        echo '<h5 class="mb-0"><i class="fas fa-bolt"></i> Fatal Error</h5></div>';
        echo '<div class="card-body"><p class="lead">A fatal error occurred and the request could not be completed.</p>';
        $loginUrl = defined('APP_URL') ? APP_URL : 'http://localhost/wallet';
        echo '<a href="' . $loginUrl . '/index.php?page=login" class="btn btn-primary mt-3">Return to Login</a>';
        echo '</div></div></div></div></div></body></html>';
        error_log('Shutdown error: ' . print_r($error, true));
    }
});

$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING) ?: 'login';

$routes = [
    'login' => ['controller' => 'AuthController', 'method' => 'login'],
    'admin_login' => ['controller' => 'AuthController', 'method' => 'adminLogin'],
    'handle_admin_login' => ['controller' => 'AuthController', 'method' => 'handleAdminLogin'],
    'admin_dashboard' => ['controller' => 'AdminController', 'method' => 'index'],
    'register' => ['controller' => 'AuthController', 'method' => 'register'],
    'handle_login' => ['controller' => 'AuthController', 'method' => 'handleLogin'],
    'handle_register' => ['controller' => 'AuthController', 'method' => 'handleRegister'],
    'verify' => ['controller' => 'AuthController', 'method' => 'verify'],
    'admin' => ['controller' => 'AdminController', 'method' => 'index'],
    'handle_verify' => ['controller' => 'AuthController', 'method' => 'handleVerify'],
    'resend_otp' => ['controller' => 'AuthController', 'method' => 'resendOTP'],
    'logout' => ['controller' => 'AuthController', 'method' => 'logout'],
    'forgot_password' => ['controller' => 'AuthController', 'method' => 'forgotPassword'],
    'handle_forgot_password' => ['controller' => 'AuthController', 'method' => 'handleForgotPassword'],
    'reset_password' => ['controller' => 'AuthController', 'method' => 'resetPassword'],
    'handle_reset_password' => ['controller' => 'AuthController', 'method' => 'handleResetPassword'],
    'dashboard' => ['controller' => 'DashboardController', 'method' => 'index'],
    'profile' => ['controller' => 'DashboardController', 'method' => 'profile'],
    'update_profile' => ['controller' => 'DashboardController', 'method' => 'updateProfile'],
    'transactions' => ['controller' => 'DashboardController', 'method' => 'transactions'],
    'analytics' => ['controller' => 'DashboardController', 'method' => 'analytics'],
    'notifications' => ['controller' => 'DashboardController', 'method' => 'notifications'],
    'change_language' => ['controller' => 'DashboardController', 'method' => 'changeLanguage'],
    'security' => ['controller' => 'DashboardController', 'method' => 'security'],
    'set_pin' => ['controller' => 'DashboardController', 'method' => 'setPin'],
    'handle_set_pin' => ['controller' => 'DashboardController', 'method' => 'handleSetPin'],
    'register_device' => ['controller' => 'AuthController', 'method' => 'registerDevice'],
    'handle_register_device' => ['controller' => 'AuthController', 'method' => 'handleRegisterDevice'],
    'biometric_login' => ['controller' => 'AuthController', 'method' => 'biometricLogin'],
    'handle_biometric_login' => ['controller' => 'AuthController', 'method' => 'handleBiometricLogin'],
    'remove_device' => ['controller' => 'AuthController', 'method' => 'removeDevice'],
    'send_money' => ['controller' => 'WalletController', 'method' => 'sendMoney'],
    'handle_send_money' => ['controller' => 'WalletController', 'method' => 'handleSendMoney'],
    'add_money' => ['controller' => 'WalletController', 'method' => 'addMoney'],
    'handle_add_money' => ['controller' => 'WalletController', 'method' => 'handleAddMoney'],
    'request_money' => ['controller' => 'WalletController', 'method' => 'requestMoney'],
    'handle_request_money' => ['controller' => 'WalletController', 'method' => 'handleRequestMoney'],
    'my_qr' => ['controller' => 'WalletController', 'method' => 'myQR'],
    'scan_qr' => ['controller' => 'WalletController', 'method' => 'scanQR'],
    'beneficiaries' => ['controller' => 'BeneficiaryController', 'method' => 'index'],
    'add_beneficiary' => ['controller' => 'BeneficiaryController', 'method' => 'add'],
    'handle_add_beneficiary' => ['controller' => 'BeneficiaryController', 'method' => 'handleAdd'],
    'remove_beneficiary' => ['controller' => 'BeneficiaryController', 'method' => 'remove'],
    'search_user' => ['controller' => 'BeneficiaryController', 'method' => 'searchUser'],
    'toggle_favorite' => ['controller' => 'BeneficiaryController', 'method' => 'toggleFavorite'],
    'beneficiary_transfer' => ['controller' => 'BeneficiaryController', 'method' => 'transfer'],
    'banks' => ['controller' => 'BankController', 'method' => 'index'],
    'add_bank' => ['controller' => 'BankController', 'method' => 'add'],
    'remove_bank' => ['controller' => 'BankController', 'method' => 'remove'],
    'set_default_bank' => ['controller' => 'BankController', 'method' => 'setDefault'],
    'withdraw' => ['controller' => 'BankController', 'method' => 'withdraw'],
    'handle_withdraw' => ['controller' => 'BankController', 'method' => 'handleWithdraw'],
    'load_money' => ['controller' => 'BankController', 'method' => 'loadMoney'],
    'handle_load_money' => ['controller' => 'BankController', 'method' => 'handleLoadMoney'],
    'bills' => ['controller' => 'BillController', 'method' => 'index'],
    'pay_bill' => ['controller' => 'BillController', 'method' => 'pay'],
    'handle_pay_bill' => ['controller' => 'BillController', 'method' => 'handlePay'],
    'bill_history' => ['controller' => 'BillController', 'method' => 'history'],
    'merchant' => ['controller' => 'MerchantController', 'method' => 'index'],
    'register_merchant' => ['controller' => 'MerchantController', 'method' => 'register'],
    'handle_register_merchant' => ['controller' => 'MerchantController', 'method' => 'handleRegister'],
    'merchant_qr' => ['controller' => 'MerchantController', 'method' => 'qr'],
    'merchant_pay' => ['controller' => 'MerchantController', 'method' => 'pay'],
    'admin_users' => ['controller' => 'AdminController', 'method' => 'users'],
    'admin_user_detail' => ['controller' => 'AdminController', 'method' => 'userDetail'],
    'freeze_user' => ['controller' => 'AdminController', 'method' => 'freezeUser'],
    'unfreeze_user' => ['controller' => 'AdminController', 'method' => 'unfreezeUser'],
    'admin_transactions' => ['controller' => 'AdminController', 'method' => 'transactions'],
    'admin_merchants' => ['controller' => 'AdminController', 'method' => 'merchants'],
    'toggle_merchant' => ['controller' => 'AdminController', 'method' => 'toggleMerchant'],
'admin_analytics' => ['controller' => 'AdminController', 'method' => 'analytics'],
    // Chatbot routes
    'chatbot' => ['controller' => 'ChatbotController', 'method' => 'message'],
    'chatbot_history' => ['controller' => 'ChatbotController', 'method' => 'history'],
    'chatbot_download' => ['controller' => 'ChatbotController', 'method' => 'download'],
    'chatbot_tickets' => ['controller' => 'ChatbotController', 'method' => 'tickets'],
    'chatbot_ticket_update' => ['controller' => 'ChatbotController', 'method' => 'ticketUpdate'],
];

if (!array_key_exists($page, $routes)) {
    http_response_code(404);
    require BASE_PATH . '/app/views/errors/404.php';
    exit;
}

$route = $routes[$page];
$controllerName = $route['controller'] ?? null;
$method = $route['method'] ?? null;

if (!$controllerName || !$method) {
    http_response_code(500);
    echo '<h1>Routing configuration error</h1>';
    exit;
}

if (!class_exists($controllerName)) {
    $controllerFile = BASE_PATH . '/app/controller/' . $controllerName . '.php';
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
    }
}

if (!class_exists($controllerName)) {
    http_response_code(500);
    echo '<h1>Controller class not found: ' . htmlspecialchars($controllerName) . '</h1>';
    exit;
}

try {
    $controller = new $controllerName();
    if (!method_exists($controller, $method)) {
        http_response_code(500);
        echo '<h1>Method not found: ' . htmlspecialchars($method) . ' in ' . htmlspecialchars($controllerName) . '</h1>';
        exit;
    }
    $controller->$method();
} catch (Throwable $e) {
    throw $e;
}
