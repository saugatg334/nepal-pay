<?php
require_once __DIR__ . '/../../app/helpers/session_helper.php';
require_once __DIR__ . '/../../app/controller/Authcontroller.php';

$auth = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    
        // Try login
        if ($auth->login($phone, $password)) {
            // Check if admin
            if (!empty($_SESSION['is_admin'])) {
                header('Location: ../index.php?path=admin-dashboard');
                exit;
            } else {
                setFlash('error', 'Admin access required. Please use admin credentials.');
                session_destroy();
            }
        }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - NepalPay</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/css/design-system.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md">
    <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-10 border border-white/20 shadow-2xl">
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-gradient-to-r from-yellow-400 to-orange-400 rounded-2xl mx-auto mb-6 flex items-center justify-center shadow-xl">
                <i class="fas fa-shield-alt text-2xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-white to-gray-200 bg-clip-text text-transparent mb-2">Admin Panel</h1>
            <p class="text-gray-300 text-lg">Secure login required</p>
        </div>

        <?php
        $flashError = getFlash('error');
        if ($flashError): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-200 p-4 rounded-2xl mb-6 backdrop-blur-sm">
                <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($flashError) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-gray-200 mb-3">Admin Phone</label>
                <input type="tel" name="phone" placeholder="984XXXXXXX" required pattern="[0-9]{10}" 
                       class="w-full px-5 py-4 bg-white/20 border border-white/30 rounded-2xl text-white placeholder-gray-400 backdrop-blur-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/50 focus:outline-none transition-all duration-300 text-lg" autocomplete="username">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-200 mb-3">Password</label>
                <div class="relative">
                    <input type="password" name="password" placeholder="••••••••" required minlength="6"
                           class="w-full px-5 py-4 bg-white/20 border border-white/30 rounded-2xl text-white placeholder-gray-400 backdrop-blur-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/50 focus:outline-none transition-all duration-300 text-lg pr-12" autocomplete="current-password">
                    <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-eye text-lg"></i>
                    </button>
                </div>
            </div>

            <button type="submit" 
                    class="w-full bg-gradient-to-r from-indigo-600 to-purple-700 hover:from-indigo-700 hover:to-purple-800 text-white font-bold py-5 px-6 rounded-2xl text-lg shadow-2xl hover:shadow-3xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm border border-white/20">
                <i class="fas fa-sign-in-alt mr-2"></i>Login to Admin
            </button>
        </form>

        <div class="mt-8 pt-8 border-t border-white/10 text-center">
            <p class="text-sm text-gray-400">Use admin credentials (phone/password from users table where is_admin=1)</p>
            <a href="../login.php" class="text-indigo-400 hover:text-indigo-300 text-sm font-semibold mt-4 inline-block transition-colors">← User Login</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.querySelector('input[type="password"]');
    const toggleBtn = document.querySelector('.fa-eye').parentElement;
    
    toggleBtn.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
});
</script>
</body>
</html>

