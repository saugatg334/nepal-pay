<?php
require_once __DIR__ . '/../../app/helper/APIClient.php';

header('Content-Type: application/json; charset=utf-8');

$sc = isset($_GET['sc_no']) ? trim($_GET['sc_no']) : '';
if (!$sc) {
    http_response_code(400);
    echo json_encode(['error' => 'sc_no is required']);
    exit;
}

$client = new APIClient();
try {
    $data = $client->getNEABill($sc);
    echo json_encode(['ok' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

