<?php
require_once __DIR__ . '/../../app/config/database.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'POST required']); exit; }

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload || empty($payload['txn_id']) || empty($payload['status'])) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'txn_id and status required']);
    exit;
}

$txn = $payload['txn_id'];
$status = $payload['status'];
$provider_response = json_encode($payload);

try {
    $db = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare('UPDATE transactions SET status = :status, provider_response = :provider_response WHERE txn_id = :txn LIMIT 1');
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':provider_response', $provider_response);
    $stmt->bindParam(':txn', $txn);
    $stmt->execute();
    echo json_encode(['ok'=>true,'txn'=>$txn,'status'=>$status]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

?>