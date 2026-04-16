<?php
/**
 * NepalPay Common Layout - User & Admin
 * Blue gradient sidebar + White navbar
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'NepalPay' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/your-kit-id.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar { background: linear-gradient(180deg, #3b82f6 0%, #1e40af 100%); }
        .glass { backdrop-filter: blur(12px); background: rgba(255,255,255,0.85); }
        .hover-lift:hover { transform: translateY(-2px); }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="sidebar text-white w-64 flex-shrink-0 p-6 relative z-10">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center text-xl font-bold">NP</div>
                    <div>
                        <div class="text-white font-bold text-xl">NepalPay</div>
                        <div class="text-blue-100 text-sm">Digital Wallet</div>
                    </div>
                </div>
                <button class="p-2 rounded-xl bg-white/10 hover:bg-white/20 sidebar-toggle md:hidden">
                    <i class="fas fa-bars text-white"></i>
                </button>
            </div>

            <nav class="space-y-2">
                <?php foreach ($menu_items ?? [] as $item): ?>
                    <a href="<?= $item['href'] ?>" class="<?= $item['active'] ? 'bg-blue-800 bg-opacity-50' : 'hover:bg-white/10' ?> flex items-center gap-3 p-4 rounded-xl group transition-all duration-200">
                        <i class="<?= $item['icon'] ?> text-white/90 group-hover:text-white"></i>
                        <span class="font-medium"><?= $item['label'] ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="absolute bottom-6 left-6 right-6">
                <div class="bg-white/10 p-4 rounded-xl">
                    <p class="text-xs opacity-80">Support</p>
                    <a href="#" class="text-sm font-medium hover:text-white">+977 980-000-0001</a>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <header class="bg-white/80 backdrop-blur-md border-b border-blue-100 shadow-sm p-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button class="sidebar-toggle md:hidden p-2 rounded-xl bg-gray-100">
                        <i class="fas fa-bars text-gray-600"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-gray-900"><?= $page_title ?></h1>
                </div>
                <div class="flex items-center gap-4">
                    <button class="p-2 text-gray-600 hover:bg-gray-100 rounded-xl relative">
                        <i class="fas fa-bell text-lg"></i>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
                    </button>
                    <div class="flex items-center gap-3 p-2 bg-gray-100 rounded-xl cursor-pointer group hover:bg-gray-200" onclick="toggleProfile()">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl flex items-center justify-center text-white font-semibold text-sm">NP</div>
                        <div class="hidden md:block">
                            <div class="font-semibold text-gray-900 text-sm"><?= $_SESSION['user_name'] ?? 'User' ?></div>
                            <div class="text-xs text-gray-500">Wallet</div>
                        </div>
                        <i class="fas fa-chevron-down text-gray-500 group-hover:text-gray-700 hidden md:block"></i>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 p-8 overflow-y-auto">
                <?= $content ?>
            </main>
        </div>
    </div>

    <!-- Profile Dropdown -->
    <div id="profileDropdown" class="fixed right-4 top-16 md:right-6 md:top-20 w-48 bg-white rounded-2xl shadow-2xl border border-gray-100 hidden z-50">
        <div class="p-4 border-b border-gray-100">
            <div class="font-semibold"><?= $_SESSION['user_name'] ?? 'User' ?></div>
            <div class="text-sm text-gray-500"><?= $_SESSION['user_id'] ?? '' ?></div>
        </div>
        <a href="?path=profile" class="flex items-center gap-3 p-4 hover:bg-gray-50 rounded-t-xl">
            <i class="fas fa-user text-gray-600"></i>
            <span>Profile</span>
        </a>
        <a href="?path=logout" class="flex items-center gap-3 p-4 hover:bg-gray-50 rounded-b-xl">
            <i class="fas fa-sign-out-alt text-red-600"></i>
            <span>Logout</span>
        </a>
    </div>

    <script>
        function toggleProfile() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#profileDropdown') && !e.target.closest('[onclick="toggleProfile()"]')) {
                document.getElementById('profileDropdown').classList.add('hidden');
            }
        });

        // Sidebar toggle for mobile
        document.querySelectorAll('.sidebar-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelector('aside').classList.toggle('hidden');
            });
        });
    </script>
</body>
</html>

