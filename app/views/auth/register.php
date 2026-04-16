<?php
$pageTitle = 'NepalPay - Register';
$bgClass = 'bg-gradient-to-br from-indigo-500 via-purple-500 to-blue-600';

ob_start();
?>
<!-- Logo -->
<div class="text-center mb-8 text-white">
    <div class="text-4xl mb-3"><i class="fas fa-wallet"></i></div>
    <h1 class="text-3xl font-bold">Join NepalPay</h1>
    <p class="text-sm opacity-80">Create your wallet</p>
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
    <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        
        <!-- Name -->
        <div>
            <label class="text-white text-sm">Full Name</label>
            <input type="text" name="name"
                class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-blue-400"
                placeholder="Enter your name" required>
        </div>
        
        <!-- Phone -->
        <div>
            <label class="text-white text-sm">Phone Number</label>
            <input type="tel" name="phone" pattern="[0-9]{10}"
                class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-blue-400"
                placeholder="98XXXXXXXX" maxlength="10" required>
        </div>
        
        <!-- Password -->
        <div>
            <label class="text-white text-sm">Password</label>
            <input type="password" name="password" minlength="6"
                class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-blue-400"
                placeholder="Min 6 characters" required>
        </div>
        
        <!-- Button -->
        <button class="w-full bg-green-600 text-white p-3 rounded-lg hover:bg-green-700 transition">
            Create Account
        </button>
        
        <!-- Links -->
        <div class="text-center text-sm mt-2">
            <span class="text-white">Already have account?</span>
            <a href="/login" class="text-green-300 ml-1">Login</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/auth.php';
