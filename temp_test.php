$_GET['page'] = 'handle_admin_login';  
$_SERVER['REQUEST_METHOD'] = 'POST';  
$_POST = ['phone' =, 'password' =, 'csrf_token' = 
ob_start();  
require 'public/index.php';  
echo ob_get_clean();  
