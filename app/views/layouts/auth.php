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
        .glass { background: rgba(255,255,255,0.15); backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.3); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center <?php echo $bgClass ?? 'bg-gradient-to-br from-blue-500 via-purple-500 to-blue-600'; ?>">
    <?php echo $content ?? ''; ?>
</body>
</html>
