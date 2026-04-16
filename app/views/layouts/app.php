<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'NepalPay'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 260px; background: linear-gradient(180deg, #1877F2 0%, #0D5AC7 100%); z-index: 100; }
        .sidebar-brand { display: flex; align-items: center; gap: 0.75rem; padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand-text { color: white; font-weight: 700; font-size: 1.25rem; }
        .sidebar-brand-subtitle { color: rgba(255,255,255,0.7); font-size: 0.75rem; }
        .sidebar-nav { padding: 1rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.2s; margin-bottom: 0.25rem; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.15); color: white; }
        .sidebar-nav .icon { width: 20px; text-align: center; }
        .sidebar-section-title { color: rgba(255,255,255,0.5); font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; margin: 1rem 0 0.5rem 0.75rem; }
        .main-content { margin-left: 260px; padding: 1.5rem; background: #f8fafc; min-height: 100vh; }
        .topbar { background: white; padding: 0.75rem 1.5rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05); position: fixed; top: 0; left: 260px; right: 0; z-index: 99; }
        .wallet-card { background: linear-gradient(135deg, #1877F2 0%, #0D5AC7 100%); border-radius: 1.5rem; padding: 1.5rem; color: white; position: relative; overflow: hidden; }
        .wallet-card::before { content: ''; position: absolute; top: -50%; right: -50%; width: 100%; height: 100%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); }
        .stats-card { padding: 1.25rem; border-radius: 1rem; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 1rem; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } .topbar { left: 0; } }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Topbar -->
    <?php include __DIR__ . '/navbar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content" style="padding-top: 4rem;">
        <!-- Flash Messages -->
        <?php if ($error = getFlash('error')): ?>
            <div class="p-4 mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success = getFlash('success')): ?>
            <div class="p-4 mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Page Content -->
        <?php echo $content ?? ''; ?>
    </main>
</body>
</html>
