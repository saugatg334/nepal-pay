<?php
$pageTitle = 'NepalPay Admin - Login';
$bgClass = 'bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800';

ob_start();
?>
<!-- Logo -->
<div class="text-center mb-8 text-white">
    <div class="text-4xl mb-3"><i class="fas fa-shield-alt"></i></div>
    <h1 class="text-3xl font-bold">NepalPay Admin</h1>
    <p class="text-sm opacity-80">Administrator Panel</p>
</div>

<!-- Error -->
<?php if ($error = getFlash('error')): ?>
    <div class="bg-red-500 text-white p-3 rounded mb-4 text-sm text-center">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Card -->
<div class="glass p-6 rounded-2xl shadow-lg">
    <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        
        <!-- Identifier -->
        <div>
            <label class="text-white text-sm">Admin Email</label>
            <input type="text" name="identifier"
                class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-blue-400"
                placeholder="admin@nepalpay.com" required>
        </div>
        
        <!-- Password -->
        <div>
            <label class="text-white text-sm">Password</label>
            <input type="password" name="password"
                class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-blue-400"
                placeholder="Password" required>
        </div>
        
        <!-- Button -->
        <button class="w-full bg-white text-blue-700 font-semibold p-3 rounded-lg hover:bg-gray-100 transition">
            Admin Login
        </button>
        
        <!-- Back Link -->
        <div class="text-center text-sm mt-4">
            <a href="/login" class="text-blue-300">← Back to User Login</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/auth.php';
