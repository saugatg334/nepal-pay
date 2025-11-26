<?php
header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sc = isset($_GET['sc_no']) ? trim($_GET['sc_no']) : '';
    if (!$sc) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'sc_no required']);
        exit;
    }
    // Mock response
    $data = [
        'consumer_name' => 'Ram Bahadur Thapa',
        'sc_no' => $sc,
        'amount' => 1240.00,
        'due_date' => date('Y-m-d', strtotime('+10 days')),
        'status' => 'UNPAID'
    ];
    echo json_encode(['ok'=>true,'data'=>$data]);
    exit;
}

if ($method === 'POST') {
    // Simulate provider accepting payment
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload || empty($payload['txn_id'])) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'txn_id required']);
        exit;
    }
    // Return simulated confirmation
    $resp = ['ok'=>true,'txn_id'=>$payload['txn_id'],'status'=>'PAID','received_at'=>date('c')];
    echo json_encode($resp);
    exit;
}

http_response_code(405);
echo json_encode(['ok'=>false,'error'=>'Method Not Allowed']);
?>