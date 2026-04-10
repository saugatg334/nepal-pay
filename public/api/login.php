<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("POST required");
    }

    session_start();

    require_once "../app/config/database.php";
    $database = new Database();
    $pdo = $database->connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $phone = trim($postData['phone'] ?? '');
    $password = $postData['password'] ?? '';

    if (empty($phone) || empty($password)) {
        throw new Exception("Phone and password required");
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? OR email = ? LIMIT 1");
    $stmt->execute([$phone, $phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        throw new Exception("Invalid credentials");
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'] ?? 'user';

    ob_end_clean();
    echo json_encode([
        "success" => true,
        "redirect" => "index.php?path=dashboard"
    ]);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    file_put_contents("debug.txt", date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
    exit;
}

restore_error_handler();
?>

