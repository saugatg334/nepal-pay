<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POST required"]);
    exit;
}

try {
    session_start();
    require_once "../app/config/database.php";
    
    $database = new Database();
    $pdo = $database->connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$identifier = trim($input['phone'] ?? $input['identifier'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Phone and password required"]);
        exit;
    }

    // Simple rate limit
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    $_SESSION['login_attempts']++;
    if ($_SESSION['login_attempts'] > 5) {
        echo json_encode(["success" => false, "message" => "Too many attempts. Try again later."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? OR email = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(["success" => false, "message" => "Invalid credentials"]);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'] ?? 'user';
    $_SESSION['login_attempts'] = 0;

    echo json_encode(["success" => true, "redirect" => "index.php?path=dashboard"]);
    
} catch (Throwable $e) {
    file_put_contents("debug.txt", date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n", FILE_APPEND);
    error_log($e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error"
    ]);
    exit;
}

restore_error_handler();
?>




