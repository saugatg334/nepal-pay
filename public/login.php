<?php
require_once __DIR__ . '/../app/controller/AuthController.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';

$auth = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $auth->login($phone, $password);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nepal Pay</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="assets/logo.svg">
</head>
<body>
        <div class="login-page">
            <div class="login-left">
                <h2>NepalPay</h2>
                <p>Send money, pay bills, and manage your finances — secure and fast payments for Nepal.</p>
                <p style="margin-top:24px;font-weight:600;opacity:0.95">Trusted • Simple • Local</p>
            </div>

            <div class="login-right">
                <?php
                $flashMessage = getFlash('success');
                if ($flashMessage) {
                        echo '<div class="flash-message flash-success">' . htmlspecialchars($flashMessage) . '</div>';
                }

                $flashError = getFlash('error');
                if ($flashError) {
                        echo '<div class="flash-message flash-error">' . htmlspecialchars($flashError) . '</div>';
                }
                ?>

                <div class="login-card-top">
                    <img src="assets/logo.svg" alt="NepalPay" class="logo-small">
                    <div>
                        <div style="font-weight:700;color:#111">Welcome back</div>
                        <div style="font-size:13px;color:#606770">Login to continue to NepalPay</div>
                    </div>
                </div>

                <h3>Login to your account</h3>
                <form method="POST" class="login-form" id="loginForm">
                    <div class="input-row" id="row-phone">
                        <input id="phone" type="text" name="phone" placeholder="Phone number (10 digits)" required pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number" maxlength="10" inputmode="numeric" autocomplete="username">
                        <span class="input-icon">📱</span>
                        <div class="input-feedback">Please enter a valid 10-digit phone number</div>
                    </div>

                    <div class="input-row" id="row-password" style="margin-top:8px;">
                        <input id="password" type="password" name="password" placeholder="Password" required minlength="6" autocomplete="current-password" style="flex:1">
                        <span class="input-icon">🔒</span>
                        <button type="button" class="show-password" id="togglePassword" aria-label="Show password">Show</button>
                        <div class="input-feedback">Password must be at least 6 characters</div>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
                        <label class="remember-row"><input type="checkbox" name="remember"> Remember me</label>
                        <a class="small-link" href="forgot-password.php">Forgot password?</a>
                    </div>

                    <div style="margin-top:16px;display:flex;flex-direction:column;gap:10px;">
                        <button type="submit">Login</button>
                        <a class="create-account" href="register.php">Create New Account</a>
                    </div>
                </form>
            </div>
        </div>
        <script>
            (function(){
                const form = document.getElementById('loginForm');
                const phone = document.getElementById('phone');
                const password = document.getElementById('password');
                const toggle = document.getElementById('togglePassword');
                const rowPhone = document.getElementById('row-phone');
                const rowPw = document.getElementById('row-password');

                function validatePhone() {
                    const re = /^[0-9]{10}$/;
                    if (re.test(phone.value.trim())) {
                        rowPhone.classList.remove('invalid'); rowPhone.classList.add('valid'); rowPhone.querySelector('.input-feedback').textContent = 'Looks good';
                        return true;
                    } else {
                        rowPhone.classList.remove('valid'); rowPhone.classList.add('invalid'); rowPhone.querySelector('.input-feedback').textContent = 'Please enter a valid 10-digit phone number';
                        return false;
                    }
                }

                function validatePassword() {
                    if (password.value && password.value.length >= 6) {
                        rowPw.classList.remove('invalid'); rowPw.classList.add('valid'); rowPw.querySelector('.input-feedback').textContent = 'OK';
                        return true;
                    } else {
                        rowPw.classList.remove('valid'); rowPw.classList.add('invalid'); rowPw.querySelector('.input-feedback').textContent = 'Password must be at least 6 characters';
                        return false;
                    }
                }

                phone.addEventListener('input', function(){
                    if (phone.value.trim().length) phone.classList.add('has-value'); else phone.classList.remove('has-value');
                    validatePhone();
                });
                password.addEventListener('input', function(){
                    if (password.value.trim().length) password.classList.add('has-value'); else password.classList.remove('has-value');
                    validatePassword();
                });

                [phone, password].forEach(i => {
                    i.addEventListener('focus', () => i.classList.add('focused'));
                    i.addEventListener('blur', () => i.classList.remove('focused'));
                });

                if (toggle && password) {
                    toggle.addEventListener('click', function(){
                        if (password.type === 'password') { password.type = 'text'; toggle.textContent = 'Hide'; } else { password.type = 'password'; toggle.textContent = 'Show'; }
                        password.focus();
                    });
                }

                form.addEventListener('submit', function(e){
                    const ok = validatePhone() & validatePassword();
                    if (!ok) {
                        e.preventDefault();
                        form.classList.remove('shake');
                        void form.offsetWidth; // reflow to restart animation
                        form.classList.add('shake');
                        let existing = document.querySelector('.flash-error');
                        if (!existing) {
                            const div = document.createElement('div');
                            div.className = 'flash-message flash-error';
                            div.textContent = 'Please fix the highlighted fields and try again.';
                            form.parentNode.insertBefore(div, form);
                            setTimeout(()=>{ if (div.parentNode) div.parentNode.removeChild(div); }, 3500);
                        }
                    }
                });
            })();
        </script>
</body>
</html>
