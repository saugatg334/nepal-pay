<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login • NepalPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .glass { backdrop-filter: blur(20px); background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); }
        .facebook-logo { background: #1877f2; }
    </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <div class="max-w-md w-full mx-auto space-y-8 px-6">
        <!-- Facebook-style logo -->
        <div class="text-center">
            <div class="mx-auto h-16 w-16 facebook-logo rounded-2xl flex items-center justify-center text-2xl font-bold text-white shadow-lg mb-6">
                f
            </div>
            <h1 class="text-4xl font-bold text-white">NepalPay</h1>
            <p class="text-white/80 text-lg">Connect with friends and the world around you on NepalPay.</p>
        </div>

        <!-- Flash messages -->
        <?php if ($flash = getFlash('success')): ?>
            <div class="bg-green-500/20 border border-green-500/50 text-green-100 p-4 rounded-xl backdrop-blur-sm animate-pulse">
                <?= htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <?php if ($flash = getFlash('error')): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-100 p-4 rounded-xl backdrop-blur-sm animate-pulse">
                <?= htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <!-- Main login form - Facebook style -->
        <div class="glass rounded-3xl p-8 shadow-2xl">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                
                <!-- Email/Phone input -->
                <div>
                    <label class="block text-sm font-semibold text-white mb-3">Email or Phone Number</label>
                    <input type="text" name="identifier" required 
                           class="w-full px-5 py-4 bg-white/30 border border-white/40 rounded-2xl text-white placeholder-white/70 backdrop-blur-md focus:outline-none focus:ring-4 focus:ring-blue-500/30 focus:border-transparent shadow-xl transition-all duration-300 text-lg"
                           placeholder="Email or phone" autocomplete="username">
                </div>

                <!-- Password input -->
                <div>
                    <label class="block text-sm font-semibold text-white mb-3">Password</label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full px-5 py-4 bg-white/30 border border-white/40 rounded-2xl text-white placeholder-white/70 backdrop-blur-md focus:outline-none focus:ring-4 focus:ring-blue-500/30 focus:border-transparent shadow-xl transition-all duration-300 text-lg"
                           placeholder="Password" autocomplete="current-password">
                </div>

                <!-- Login button + forgot password -->
                <div class="space-y-4">
                    <button type="submit" 
                            class="w-full facebook-logo hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-2xl text-xl shadow-2xl hover:shadow-3xl hover:-translate-y-px transform transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-500/50">
                        Log In
                    </button>
                    
                    <div class="text-center py-4">
                        <a href="?path=user/forgotPassword" class="block text-blue-300 hover:text-white font-semibold text-sm transition-colors">
                            Forgot Password?
                        </a>
                    </div>
                </div>
            </form>

            <!-- Divider -->
            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-white/20"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-gradient-to-r from-blue-600/20 to-purple-600/20 text-white/80 backdrop-blur-sm rounded-full py-1">
                        Create new account
                    </span>
                </div>
            </div>

            <!-- Create account button -->
            <div>
                <a href="?path=user/register" 
                   class="w-full block bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-2xl text-lg text-center shadow-2xl hover:shadow-3xl hover:-translate-y-px transform transition-all duration-300">
                   Sign Up for NepalPay
                </a>
            </div>

            <!-- Biometric option -->
            <div class="mt-8 pt-8 border-t border-white/10 text-center">
                <button type="button" onclick="simulateBiometric()" 
                        class="flex items-center justify-center gap-3 mx-auto w-3/4 bg-white/10 hover:bg-white/20 text-white py-3 px-6 rounded-2xl backdrop-blur-sm transition-all duration-300 border border-white/20 text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Login with Face ID / Fingerprint
                </button>
            </div>
        </div>

        <!-- Admin login link (bottom) -->
        <div class="text-center mt-12">
            <a href="?path=admin/login" class="inline-flex items-center gap-2 text-blue-300 hover:text-white font-medium text-sm transition-colors">
                <i class="fas fa-user-shield text-lg"></i> Admin Login
            </a>
        </div>
    </div>

    <script>
        function simulateBiometric() {
            if (confirm('Simulate biometric login with Face ID/Fingerprint?')) {
                document.querySelector('form').submit();
            }
        }

        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="identifier"]').focus();
        });

        // Enter key submits form
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('form').submit();
            }
        });
    </script>
</body>
</html>
