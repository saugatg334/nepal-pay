<?php
require_once __DIR__ . '/../config/database.php';

class Biometric {
    private $conn;
    private $table_name = 'biometric_credentials';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function registerCredential($user_id, $credential_id, $public_key, $device_name) {
        try {
            $query = "INSERT INTO {$this->table_name} (user_id, credential_id, public_key, device_name) 
                      VALUES (:user_id, :credential_id, :public_key, :device_name)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':credential_id', $credential_id);
            $stmt->bindParam(':public_key', $public_key);
            $stmt->bindParam(':device_name', $device_name);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Credential registration failed: " . $e->getMessage());
        }
    }

    public function getCredentials($user_id) {
        try {
            $query = "SELECT * FROM {$this->table_name} WHERE user_id = :user_id ORDER BY last_used DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch credentials: " . $e->getMessage());
        }
    }

    public function getCredential($credential_id) {
        try {
            $query = "SELECT * FROM {$this->table_name} WHERE credential_id = :credential_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':credential_id', $credential_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch credential: " . $e->getMessage());
        }
    }

    public function updateLastUsed($credential_id) {
        try {
            $query = "UPDATE {$this->table_name} SET last_used = NOW() WHERE credential_id = :credential_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':credential_id', $credential_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Failed to update last used: " . $e->getMessage());
        }
    }

    public function deleteCredential($credential_id) {
        try {
            $query = "DELETE FROM {$this->table_name} WHERE credential_id = :credential_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':credential_id', $credential_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Failed to delete credential: " . $e->getMessage());
        }
    }

    // Generate challenge for WebAuthn
    public function generateChallenge() {
        return base64url_encode(random_bytes(32));
    }

    // Verify WebAuthn assertion
    public function verifyAssertion($credential_id, $client_data_json, $authenticator_data, $signature, $public_key) {
        // Simplified verification (in production, use full WebAuthn library)
        // This would validate signature against public key
        return true; // Placeholder for actual verification
    }
}

// Helper function
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
?>

