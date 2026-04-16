<?php
session_start();
unset($_SESSION['login_attempts'], $_SESSION
['lock_until']);
setFlash('success', 'Your account has been unlocked. You can try logging in again.');
header("Location: login.php");      
exit;