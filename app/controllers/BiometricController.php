<?php
require_once __DIR__ . '/../models/Biometric.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/session_helper.php';

class BiometricController {
    private $biometricModel;
    private $userModel;

    public function __construct() {
        $this->biometricModel = new Biometric();
        $this->userModel = new User();
    }

    public function generateRegistrationOptions($user_id) {
        $user = $this->userModel->getUserById($user_id);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        $challenge = $this->biometricModel->generateChallenge();
        $_SESSION['webauthn_challenge'] = $challenge;

        $options = [
            'publicKey' => [
                'challenge' => base64url_encode(random_bytes(32)),
                'rp' => [
                    'name' => 'Nepal Pay',
                    'id' => $_SERVER['HTTP_HOST']
                ],
                'user' => [
                    'id' => base64_encode($user['id']),
                    'name' => $user['phone'],
                    'displayName' => $user['name']
                ],
                'pubKeyCredParams' => [
                    ['alg' => -7, 'type' => 'public-key'], // ES256
                    ['alg' => -257, 'type' => 'public-key'] // RS256
                ],
                'authenticatorSelection' => [
                    'userVerification' => 'preferred'
                ],
                'timeout' => 60000
            ]
        ];

        echo json_encode(['publicKey' => $options['publicKey']]);
    }

    public function verifyRegistration($data) {
        $challenge = $_SESSION['webauthn_challenge'] ?? '';
        if (!$challenge || !hash_equals(base64url_decode($data['response']['challenge'] ?? ''), base64url_decode($challenge))) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid challenge']);
            exit;
        }

        // Store credential (simplified)
        $this->biometricModel->registerCredential(
            $data['user_id'],
            $data['id'],
            $data['response']['publicKey'],
            $data['device_name'] ?? 'Unknown Device'
        );

        // Enable biometric for user
        $this->userModel->conn->prepare("UPDATE users SET biometric_enabled = 1 WHERE id = ?")->execute([$data['user_id']]);

        setFlash('success', 'Biometric device registered successfully!');
        echo json_encode(['success' => true]);
    }

    public function generateAuthenticationOptions($user_id) {
        $credentials = $this->biometricModel->getCredentials($user_id);
        if (empty($credentials)) {
            http_response_code(404);
            echo json_encode(['error' => 'No registered credentials']);
            exit;
        }

        $allowCredentials = [];
        foreach ($credentials as $cred) {
            $allowCredentials[] = [
                'id' => $cred['credential_id'],
                'type' => 'public-key'
            ];
        }

        $options = [
            'publicKey' => [
                'challenge' => base64url_encode(random_bytes(32)),
                'timeout' => 60000,
                'allowCredentials' => $allowCredentials,
                'userVerification' => 'preferred'
            ]
        ];

        $_SESSION['webauthn_challenge'] = $options['publicKey']['challenge'];
        echo json_encode($options);
    }

    public function verifyAuthentication($data) {
        $challenge = $_SESSION['webauthn_challenge'] ?? '';
        if (!$challenge) {
            http_response_code(400);
            echo json_encode(['error' => 'No challenge']);
            exit;
        }

        $credential = $this->biometricModel->getCredential($data['id']);
        if (!$credential) {
            http_response_code(404);
            echo json_encode(['error' => 'Credential not found']);
            exit;
        }

        // Verify signature (simplified)
        if ($this->biometricModel->verifyAssertion(
            $credential['credential_id'],
            $data['response']['clientDataJSON'],
            $data['response']['authenticatorData'],
            $data['response']['signature'],
            $credential['public_key']
        )) {
            $this->biometricModel->updateLastUsed($credential['credential_id']);

            // Log biometric login
            $this->logBiometricLogin($credential['user_id']);

            $_SESSION['user_id'] = $credential['user_id'];
            setFlash('success', 'Biometric login successful!');
            echo json_encode(['success' => true, 'redirect' => '/dashboard.php']);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication failed']);
        }
    }

    private function logBiometricLogin($user_id) {
        $database = new Database();
        $conn = $database->connect();
        $stmt = $conn->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent, login_method, success) VALUES (?, ?, ?, 'biometric', 1)");
        $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    }
}
?>

