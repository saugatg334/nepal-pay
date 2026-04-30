<!DOCTYPE html>
<html lang="<?php echo getLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | NepalPay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .error-card { 
            background: #fff; 
            border-radius: 16px; 
            padding: 48px; 
            text-align: center; 
            max-width: 520px; 
            width: 100%; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }
        .error-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #007bff, #00d4ff);
        }
        .error-icon { 
            font-size: 80px; 
            color: #6c757d;
            margin-bottom: 24px;
            opacity: 0.3;
        }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: #007bff;
            line-height: 1;
            margin-bottom: 16px;
            text-shadow: 0 4px 8px rgba(0,123,255,0.1);
        }
        .home-link {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="fas fa-compass"></i>
        </div>
        <div class="error-code">404</div>
        <h1 class="h3 mb-3 text-dark">Page Not Found</h1>
        <p class="text-muted mb-4">
            The page you're looking for doesn't exist or has been moved.
        </p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="<?php echo APP_URL; ?>/index.php?page=dashboard" class="btn btn-primary home-link">
                <i class="fas fa-home"></i> Go to Dashboard
            </a>
            <button onclick="history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Go Back
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
