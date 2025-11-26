<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/controller/AuthController.php';

$auth = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $auth->register($name, $phone, $password, $confirmPassword);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Nepal Pay</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="assets/logo.svg">
</head>
<body>
        <div class="login-page">
            <div class="login-left">
                <h2>Join NepalPay</h2>
                <p>Create your NepalPay wallet to send money, pay bills, and get cashback offers.</p>
                <p style="margin-top:24px;font-weight:600;opacity:0.95">Fast • Secure • Local</p>
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
                        <div style="font-weight:700;color:#111">Create account</div>
                        <div style="font-size:13px;color:#606770">Sign up to get started</div>
                    </div>
                </div>

                <h3>Create your NepalPay account</h3>
                <form method="POST" class="login-form" id="registerForm">
                    <div class="input-row" id="row-name">
                        <input id="name" type="text" name="name" placeholder="Full Name" required minlength="2" maxlength="100" autocomplete="name">
                        <span class="input-icon">👤</span>
                        <div class="input-feedback">Please enter your name</div>
                    </div>

                    <div class="input-row" id="row-phone" style="margin-top:8px;">
                        <input id="phone" type="text" name="phone" placeholder="Phone Number (10 digits)" required pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number" maxlength="10" inputmode="numeric" autocomplete="username">
                        <span class="input-icon">📱</span>
                        <div class="input-feedback">Please enter a valid 10-digit phone number</div>
                    </div>

                    <div class="input-row" id="row-password" style="margin-top:8px;">
                        <input id="password" type="password" name="password" placeholder="Password (min 6 chars)" required minlength="6" autocomplete="new-password" style="flex:1">
                        <span class="input-icon">🔒</span>
                        <button type="button" class="show-password" id="togglePasswordReg" aria-label="Show password">Show</button>
                        <div class="input-feedback">Password must be at least 6 characters</div>
                    </div>

                    <div class="input-row" id="row-confirm" style="margin-top:8px;">
                        <input id="confirm_password" type="password" name="confirm_password" placeholder="Confirm Password" required autocomplete="new-password" style="flex:1">
                        <span class="input-icon">🔒</span>
                        <button type="button" class="show-password" id="toggleConfirmReg" aria-label="Show confirm password">Show</button>
                        <div class="input-feedback">Passwords must match</div>
                    </div>

                    <div style="margin-top:16px;display:flex;flex-direction:column;gap:10px;">
                        <button type="submit">Register</button>
                        <a class="create-account" href="login.php">Already have an account? Login</a>
                    </div>
                </form>
            </div>
        </div>
        <script>
            (function(){
                const form = document.getElementById('registerForm');
                const name = document.getElementById('name');
                const phone = document.getElementById('phone');
                const password = document.getElementById('password');
                const confirm = document.getElementById('confirm_password');
                const toggle = document.getElementById('togglePasswordReg');
                const toggle2 = document.getElementById('toggleConfirmReg');
                const rowName = document.getElementById('row-name');
                const rowPhone = document.getElementById('row-phone');
                const rowPw = document.getElementById('row-password');
                const rowConfirm = document.getElementById('row-confirm');

                function validateName(){
                    if (name.value.trim().length >= 2) { rowName.classList.remove('invalid'); rowName.classList.add('valid'); rowName.querySelector('.input-feedback').textContent='OK'; return true; }
                    rowName.classList.remove('valid'); rowName.classList.add('invalid'); rowName.querySelector('.input-feedback').textContent='Please enter your name'; return false;
                }

                function validatePhone(){
                    const re = /^[0-9]{10}$/;
                    if (re.test(phone.value.trim())) { rowPhone.classList.remove('invalid'); rowPhone.classList.add('valid'); rowPhone.querySelector('.input-feedback').textContent='OK'; return true; }
                    rowPhone.classList.remove('valid'); rowPhone.classList.add('invalid'); rowPhone.querySelector('.input-feedback').textContent='Please enter a valid 10-digit phone number'; return false;
                }

                function validatePwConfirm(){
                    if (password.value && password.value.length>=6) { rowPw.classList.remove('invalid'); rowPw.classList.add('valid'); rowPw.querySelector('.input-feedback').textContent='OK'; } else { rowPw.classList.remove('valid'); rowPw.classList.add('invalid'); rowPw.querySelector('.input-feedback').textContent='Password must be at least 6 characters'; }
                    if (confirm.value === password.value && confirm.value.length) { rowConfirm.classList.remove('invalid'); rowConfirm.classList.add('valid'); rowConfirm.querySelector('.input-feedback').textContent='Passwords match'; return password.value.length>=6; }
                    rowConfirm.classList.remove('valid'); rowConfirm.classList.add('invalid'); rowConfirm.querySelector('.input-feedback').textContent='Passwords must match'; return false;
                }

                [name, phone, password, confirm].forEach(i => {
                    i.addEventListener('focus', ()=>i.classList.add('focused'));
                    i.addEventListener('blur', ()=>i.classList.remove('focused'));
                });

                name.addEventListener('input', ()=>{ if(name.value.trim().length) name.classList.add('has-value'); else name.classList.remove('has-value'); validateName(); });
                phone.addEventListener('input', ()=>{ if(phone.value.trim().length) phone.classList.add('has-value'); else phone.classList.remove('has-value'); validatePhone(); });
                password.addEventListener('input', ()=>{ if(password.value.trim().length) password.classList.add('has-value'); else password.classList.remove('has-value'); validatePwConfirm(); });
                confirm.addEventListener('input', ()=>{ if(confirm.value.trim().length) confirm.classList.add('has-value'); else confirm.classList.remove('has-value'); validatePwConfirm(); });

                if (toggle && password) toggle.addEventListener('click', ()=>{ if(password.type==='password'){password.type='text'; toggle.textContent='Hide';} else {password.type='password'; toggle.textContent='Show';} password.focus(); });
                if (toggle2 && confirm) toggle2.addEventListener('click', ()=>{ if(confirm.type==='password'){confirm.type='text'; toggle2.textContent='Hide';} else {confirm.type='password'; toggle2.textContent='Show';} confirm.focus(); });

                form.addEventListener('submit', function(e){
                    const ok = validateName() & validatePhone() & validatePwConfirm();
                    if(!ok){ e.preventDefault(); form.classList.remove('shake'); void form.offsetWidth; form.classList.add('shake'); let existing = document.querySelector('.flash-error'); if(!existing){ const div=document.createElement('div'); div.className='flash-message flash-error'; div.textContent='Please fix highlighted fields'; form.parentNode.insertBefore(div, form); setTimeout(()=>{ if(div.parentNode) div.parentNode.removeChild(div); },3500);} }
                });
            })();
        </script>
</body>
</html>
