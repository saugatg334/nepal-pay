<?php
require_once __DIR__ . '/../app/controllers/BiometricController.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';

$controller = new BiometricController();

if (isset($_POST['phone'])) {
    $phone = trim($_POST['phone']);
    $user = (new User())->findUserByPhone($phone);
    
    if ($user && $user['biometric_enabled']) {
        header('Content-Type: application/json');
        echo json_encode($controller->generateAuthenticationOptions($user['id']));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify') {
    header('Content-Type: application/json');
    $controller->verifyAuthentication(json_decode(file_get_contents('php://input'), true));
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Biometric Login - Nepal Pay</title>
    <script src="https://cdn.jsdelivr.net/npm/@simplewebauthn/browser@8/dist/bundle/index.min.js"></script>
    <style>
        body { font-family: system-ui; max-width: 400px; margin: 100px auto; padding: 20px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; }
        button { width: 100%; padding: 12px; background: #d32f2f; color: white; border: none; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Login with Biometrics</h1>
    <p>Enter your phone number to use fingerprint/face ID</p>
    
    <form id="phoneForm">
        <input type="tel" id="phone" placeholder="Phone number (984XXXXXXX)" pattern="[0-9]{10}" required>
        <button type="submit">Use Biometrics</button>
    </form>
    
    <div id="status"></div>

    <script>
        document.getElementById('phoneForm').onsubmit = async (e) => {
            e.preventDefault();
            const phone = document.getElementById('phone').value;
            const statusEl = document.getElementById('status');
            
            statusEl.innerHTML = 'Checking credentials...';
            
            try {
                const response = await fetch('/biometric-login.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `phone=${phone}`
                });
                
                const options = await response.json();
                
                if (options.error) {
                    statusEl.textContent = options.error;
                    return;
                }
                
                statusEl.innerHTML = 'Touch fingerprint/face ID sensor...';
                
                const credential = await SimpleWebAuthnBrowser.authenticate(options.publicKey);
                
                statusEl.innerHTML = 'Verifying...';
                
                const verifyResponse = await fetch('/biometric-login.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'verify',
                        ...credential
                    })
                });
                
                const result = await verifyResponse.json();
                if (result.success) {
                    statusEl.innerHTML = `✅ Login successful! Redirecting... <script>window.location.href="${result.redirect}";</script>`;
                } else {
                    statusEl.textContent = result.error || 'Login failed';
                }
            } catch (err) {
                statusEl.textContent = 'Error: ' + err.message;
            }
        };
    </script>
</body>
</html>

