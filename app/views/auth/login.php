<?php
$pageTitle = 'NepalPay Login';
$bgClass = 'bg-gradient-to-br from-indigo-500 via-purple-500 to-blue-600';

ob_start();
?>
<!-- Logo -->
<div class="text-center mb-8 text-white">
    <div class="text-4xl mb-3"><i class="fas fa-wallet"></i></div>
    <h1 class="text-3xl font-bold">NepalPay</h1>
    <p class="text-sm opacity-80">Secure Digital Wallet</p>
</div>

<!-- Error -->
<?php if ($error = getFlash('error')): ?>
    <div class="bg-red-500 text-white p-3 rounded mb-4 text-sm text-center">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Success -->
<?php if ($success = getFlash('success')): ?>
    <div class="bg-green-500 text-white p-3 rounded mb-4 text-sm text-center">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<!-- Card -->
<div class="glass p-6 rounded-2xl shadow-lg">
    <form method="POST" id="loginForm" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        
        <!-- Identifier -->
        <div class="relative">
            <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
            <input type="text" name="identifier"
                class="w-full pl-10 p-3 rounded-lg border focus:ring-2 focus:ring-blue-400"
                placeholder="Phone or Email" required>
        </div>
        
        <!-- Password -->
        <div class="relative">
            <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
            <input type="password" name="password" id="password"
                class="w-full pl-10 pr-10 p-3 rounded-lg border focus:ring-2 focus:ring-blue-400"
                placeholder="Password" required>
            <span onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer">
                <i class="fas fa-eye" id="eye"></i>
            </span>
        </div>
        
        <!-- Button -->
        <button class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition">
            Login
        </button>
        
        <!-- Links -->
        <div class="text-center text-sm mt-2">
            <a href="/forgot-password" class="text-blue-200">Forgot password?</a><br>
            <a href="/register" class="text-green-300">Create account</a>
        </div>
        
        <!-- Admin Link -->
        <div class="text-center text-sm mt-4 pt-4 border-t border-white/20">
            <a href="/admin/login" class="text-blue-300 text-xs">Admin Login</a>
        </div>
    </form>
</div>

<script>
function togglePassword() {
    const pwd = document.getElementById('password');
    const eye = document.getElementById('eye');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
    eye.classList.toggle('fa-eye-slash');
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/auth.php';
