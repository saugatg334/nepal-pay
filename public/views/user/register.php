<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up • NepalPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .glass { backdrop-filter: blur(20px); background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); }
    </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-white/20 rounded-2xl flex items-center justify-center text-2xl font-bold">NP</div>
            <h1 class="mt-6 text-3xl font-bold text-white">Join NepalPay</h1>
            <p class="mt-2 text-white/80 text-sm">Create your account to get started</p>
        </div>

        <?php if ($flash = getFlash('success')): ?>
            <div class="bg-green-500/20 border border-green-500/50 text-green-100 p-4 rounded-xl">
                <?= htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <?php if ($flash = getFlash('error')): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-100 p-4 rounded-xl">
                <?= htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <div class="glass rounded-3xl p-8 shadow-2xl">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                
                <div>
                    <label class="block text-sm font-medium text-white/90 mb-2">Full Name</label>
                    <input type="text" name="full_name" required 
                           class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-white/50"
                           placeholder="Enter your full name">
                </div>

                <div>
                    <label class="block text-sm font-medium text-white/90 mb-2">Phone Number</label>
                    <input type="tel" name="phone" required pattern="[0-9]{10}" maxlength="10"
                           class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-white/50"
                           placeholder="98xxxxxxxx">
                </div>

                <div>
                    <label class="block text-sm font-medium text-white/90 mb-2">Email</label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-white/50"
                           placeholder="your@email.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-white/90 mb-2">Password</label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-white/50"
                           placeholder="Create strong password">
                </div>

                <button type="submit" 
                        class="w-full bg-white text-gray-900 font-semibold py-4 px-4 rounded-xl text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all duration-200">
                    Create Account
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-white/70">Already have account? 
                    <a href="login" class="font-semibold hover:text-white">Log in</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

