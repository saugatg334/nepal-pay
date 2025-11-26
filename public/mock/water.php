<?php
header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $cid = isset($_GET['customer_id']) ? trim($_GET['customer_id']) : '';
    if (!$cid) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'customer_id required']); exit; }
    $data = [ 'customer_name'=>'Sita Koirala', 'customer_id'=>$cid, 'amount'=>560.50, 'due_date'=>date('Y-m-d',strtotime('+7 days')), 'status'=>'UNPAID' ];
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