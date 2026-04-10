<?php
/**
 * Bill Lookup API - Multi-Provider Support
 * Handles bill lookups for all providers (NEA, KUKL, ISP, Mobile, etc.)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Get provider and customer_id from either GET or POST
    $provider = isset($_GET['provider']) ? trim($_GET['provider']) : '';
    $customer_id = isset($_GET['customer_id']) ? trim($_GET['customer_id']) : '';
    
    // Also support POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $provider = $provider ?: ($input['provider'] ?? '');
            $customer_id = $customer_id ?: ($input['customer_id'] ?? '');
        }
    }

    // ===== VALIDATION =====
    if (empty($provider) || empty($customer_id)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing provider or customer_id',
            'required' => ['provider' => 'string', 'customer_id' => 'string']
        ]);
        exit;
    }

    // Sanitize inputs
    $provider = preg_replace('/[^a-zA-Z0-9_-]/i', '', $provider);
    
    // Support numeric provider IDs (from pay.php biller_id)
    $idToName = [
        '1' => 'NEA',
        '2' => 'NCELL',
        '3' => 'WORLDLINK',
        '4' => 'NTC',
        '5' => 'SMARTCELL'
    ];
    if (isset($idToName[$provider])) {
        $provider = $idToName[$provider];
    }
    
    $customer_id = htmlspecialchars($customer_id, ENT_QUOTES, 'UTF-8');

    if (strlen($customer_id) < 2 || strlen($customer_id) > 100) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Customer ID must be 2-100 characters'
        ]);
        exit;
    }

    // ===== MOCK DATABASE OF BILLS =====
    // In production, these would be fetched from actual provider APIs
    $billDatabase = [
        'NEA' => [
            'NCELL100118' => ['consumer_name' => 'Raj Kumar Sharma', 'amount' => 1850, 'status' => 'pending', 'due_date' => '2026-04-15'],
            'NCELL100726' => ['consumer_name' => 'Priya Paudel', 'amount' => 2100, 'status' => 'pending', 'due_date' => '2026-04-20'],
            'NCELL101367' => ['consumer_name' => 'Amit Singh', 'amount' => 950, 'status' => 'pending', 'due_date' => '2026-04-10'],
            'saugatg_fnigd' => ['consumer_name' => 'Saugat Giri', 'amount' => 1200, 'status' => 'pending', 'due_date' => '2026-04-25'],
            'test123' => ['consumer_name' => 'Test User', 'amount' => 500, 'status' => 'pending', 'due_date' => '2026-04-30'],
            '123456' => ['consumer_name' => 'John Doe', 'amount' => 2200, 'status' => 'overdue', 'due_date' => '2026-04-05'],
        ],
        'KUKL' => [
            'NCELL100118' => ['consumer_name' => 'Raj Kumar Sharma', 'amount' => 450, 'status' => 'pending', 'due_date' => '2026-04-15'],
            'NCELL100726' => ['consumer_name' => 'Priya Paudel', 'amount' => 325, 'status' => 'pending', 'due_date' => '2026-04-20'],
            'saugatg_fnigd' => ['consumer_name' => 'Saugat Giri', 'amount' => 280, 'status' => 'pending', 'due_date' => '2026-04-25'],
            'test123' => ['consumer_name' => 'Test User', 'amount' => 150, 'status' => 'pending', 'due_date' => '2026-04-30'],
        ],
        'WORLDLINK' => [
            'NCELL100118' => ['consumer_name' => 'Raj Kumar Sharma', 'amount' => 1500, 'status' => 'active', 'due_date' => '2026-04-15'],
            'NCELL100726' => ['consumer_name' => 'Priya Paudel', 'amount' => 1200, 'status' => 'active', 'due_date' => '2026-04-20'],
            'NCELL101367' => ['consumer_name' => 'Amit Singh', 'amount' => 999, 'status' => 'active', 'due_date' => '2026-04-10'],
            'saugatg_fnigd' => ['consumer_name' => 'Saugat Giri', 'amount' => 1400, 'status' => 'active', 'due_date' => '2026-04-25'],
            'test123' => ['consumer_name' => 'Test User', 'amount' => 800, 'status' => 'active', 'due_date' => '2026-04-30'],
        ],
        'NCELL' => [
            'NCELL100118' => ['consumer_name' => 'Raj Kumar Sharma', 'amount' => 765, 'status' => 'active', 'due_date' => '2026-04-12'],
            'NCELL100726' => ['consumer_name' => 'Priya Paudel', 'amount' => 654, 'status' => 'active', 'due_date' => '2026-04-18'],
            'saugatg_fnigd' => ['consumer_name' => 'Saugat Giri', 'amount' => 899, 'status' => 'active', 'due_date' => '2026-04-22'],
            'test123' => ['consumer_name' => 'Test User', 'amount' => 432, 'status' => 'active', 'due_date' => '2026-04-28'],
        ],
    ];

    // ===== LOOKUP LOGIC =====
    $provider_upper = strtoupper($provider);
    
    // Check if provider exists
    if (!array_key_exists($provider_upper, $billDatabase)) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Provider not found: ' . htmlspecialchars($provider),
            'available_providers' => array_keys($billDatabase)
        ]);
        exit;
    }

    // Lookup customer in provider database
    $providerBills = $billDatabase[$provider_upper];
    
    // Try exact match first
    if (isset($providerBills[$customer_id])) {
        $bill = $providerBills[$customer_id];
        
        // ===== SUCCESS RESPONSE =====
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'provider' => $provider,
            'customer_id' => $customer_id,
            'consumer_name' => $bill['consumer_name'],
            'amount' => $bill['amount'],
            'status' => $bill['status'],
            'due_date' => $bill['due_date'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // Try case-insensitive match
    foreach ($providerBills as $key => $bill) {
        if (strtolower($key) === strtolower($customer_id)) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'provider' => $provider,
                'customer_id' => $customer_id,
                'consumer_name' => $bill['consumer_name'],
                'amount' => $bill['amount'],
                'status' => $bill['status'],
                'due_date' => $bill['due_date'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
        }
    }

    // ===== NOT FOUND =====
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'Customer ID not found for this provider',
        'provider' => $provider,
        'customer_id' => $customer_id,
        'suggestions' => 'Try: NCELL100118, NCELL100726, NCELL101367, saugatg_fnigd, or test123'
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}
?>
