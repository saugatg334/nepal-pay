<?php
require_once __DIR__ . '/../app/controllers/BiometricController.php';
require_once __DIR__ . '/../app/helpers/auth_helper.php';

requireUser();

$controller = new BiometricController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        header('Content-Type: application/json');
        $controller->generateRegistrationOptions($_SESSION['user_id']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register Biometric - Nepal Pay</title>
    <script src="https://cdn.jsdelivr.net/npm/@simplewebauthn/browser@8/dist/bundle/index.min.js"></script>
</head>
<body>
    <h1>Register Biometric Device</h1>
    <button id="registerBtn">Register Fingerprint/Face ID</button>
    <div id="status"></div>

    <script>
        document.getElementById('registerBtn').onclick = async () => {
            const statusEl = document.getElementById('status');
            statusEl.textContent = 'Creating credential...';

            const publicKey = await fetch('/biometric-register.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=register'
            }).then(r => r.json()).then(r => r.publicKey);

            try {
                const credential = await SimpleWebAuthnBrowser.register(publicKey);
                statusEl.textContent = 'Verifying...';

                const response = await fetch('/biometric-register.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(credential)
                });

                const result = await response.json();
                if (result.success) {
                    statusEl.innerHTML = '✅ Biometric registered! <a href="/dashboard.php">Back to Dashboard</a>';
                } else {
                    statusEl.textContent = '❌ Failed: ' + result.error;
                }
            } catch (err) {
                statusEl.textContent = 'Error: ' + err.message;
            }
        };
    </script>
</body>
</html>

