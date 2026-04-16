<!-- Navbar Component -->
<?php 
$pageTitle = $pageTitle ?? 'Dashboard';
$balance = $balance ?? 0;
$isAdmin = $_SESSION['is_admin'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'User';
$initials = strtoupper(substr($userName, 0, 1));
?>

<header class="topbar">
    <div class="flex items-center gap-4">
        <h2 class="font-semibold text-gray-700"><?php echo $pageTitle; ?></h2>
    </div>
    
    <div class="flex items-center gap-4">
        <!-- Search (optional) -->
        <div class="hidden md:block relative">
            <input type="text" placeholder="Search..." class="bg-gray-100 rounded-full px-4 py-2 text-sm w-48 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <!-- Notifications -->
        <button class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-600 hover:bg-gray-200">
            <i class="fas fa-bell"></i>
        </button>
        
        <!-- Profile Dropdown -->
        <div class="relative">
            <button onclick="toggleDropdown('profileDropdown')" class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                    <?php echo $initials; ?>
                </div>
                <div class="hidden md:block text-left">
                    <div class="text-sm font-medium"><?php echo htmlspecialchars($userName); ?></div>
                    <div class="text-xs text-gray-500"><?php echo $isAdmin ? 'Administrator' : 'Rs ' . number_format($balance, 2); ?></div>
                </div>
                <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
            </button>
            
            <!-- Dropdown Menu -->
            <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                <?php if (!$isAdmin): ?>

<a href="<?php echo BASE_URL ?>/wallet" class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:bg-gray-100">
    <i class="fas fa-wallet w-5"></i> Wallet
</a>

                <?php endif; ?>

<a href="<?php echo BASE_URL ?>/profile" class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:bg-gray-100">
    <i class="fas fa-user w-5"></i> Profile
</a>


<a href="<?php echo BASE_URL ?>/change-password" class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:bg-gray-100">
    <i class="fas fa-lock w-5"></i> Change Password
</a>

                <div class="border-t my-1"></div>

<a href="<?php echo BASE_URL ?>/logout" class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50">
    <i class="fas fa-sign-out-alt w-5"></i> Logout
</a>

            </div>
        </div>
    </div>
</header>

<script>
function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.relative')) {
        document.querySelectorAll('[id*="Dropdown"]').forEach(el => {
            if (!el.contains(e.target)) {
                el.classList.add('hidden');
            }
        });
    }
});
</script>
