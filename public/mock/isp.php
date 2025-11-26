<?php
header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $acct = isset($_GET['account']) ? trim($_GET['account']) : '';
    if (!$acct) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'account required']); exit; }
    $data = [ 'customer_name'=>'Binod Sharma', 'account'=>$acct, 'amount'=>1299.00, 'due_date'=>date('Y-m-d',strtotime('+5 days')), 'status'=>'UNPAID' ];
    echo json_encode(['ok'=>true,'data'=>$data]); exit;
}

if ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload || empty($payload['txn_id'])) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'txn_id required']); exit; }
    $resp = ['ok'=>true,'txn_id'=>$payload['txn_id'],'status'=>'PAID','received_at'=>date('c')];
    echo json_encode($resp); exit;
}

http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method Not Allowed']);
?>