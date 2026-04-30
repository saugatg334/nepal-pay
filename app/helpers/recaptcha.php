<?php
class ReCAPTCHA {
    public static function getScript($siteKey) {
        return '
<script src="https://www.google.com/recaptcha/api.js?render=' . $siteKey . '"></script>
<script>
grecaptcha.ready(function() {
    grecaptcha.execute("' . $siteKey . '", {action: "login"}).then(function(token) {
        document.getElementById("recaptcha_token").value = token;
    });
});
</script>';
    }
    
    public static function verify($token) {
        $secret = Config::get('RECAPTCHA_SECRET_KEY', '');
        if (empty($secret)) return true; // Dev mode
        
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$token}");
        $data = json_decode($response, true);
        return $data['success'] ?? false;
    }
}
?>

