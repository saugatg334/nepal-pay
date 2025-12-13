<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->connect();
        } catch (Exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function register($name, $phone, $password) {
        // Validate input
        if (empty($name) || empty($phone) || empty($password)) {
            throw new Exception("All fields are required.");
        }

        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters long.");
        }

        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            throw new Exception("Phone number must be 10 digits.");
        }

        try {
            $query = "INSERT INTO " . $this->table_name . " (name, phone, password) VALUES (:name, :phone, :password)";
            $stmt = $this->conn->prepare($query);

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':password', $hashed_password);

            if ($stmt->execute()) {
                return true;
            }
            return false;
        } catch (PDOException $e) {
            throw new Exception("Registration failed: " . $e->getMessage());
        }
    }

    public function findUserByPhone($phone) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE phone = :phone";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':phone', $phone);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return false;
        } catch (PDOException $e) {
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }

    public function getUserById($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return false;
        } catch (PDOException $e) {
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }

    public function updateWalletBalance($id, $amount) {
        try {
            $query = "UPDATE " . $this->table_name . " SET wallet_balance = wallet_balance + :amount WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Balance update failed: " . $e->getMessage());
        }
    }

    public function getWalletBalance($id) {
        try {
            $query = "SELECT wallet_balance FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['wallet_balance'];
            }
            return 0.00;
        } catch (PDOException $e) {
            throw new Exception("Balance retrieval failed: " . $e->getMessage());
        }
    }

    public function recordTransaction($sender_id, $receiver_id, $amount, $type, $description = null, $txn_id = null, $provider = null, $customer_ref = null, $method = null, $provider_response = null, $status = 'completed') {
        try {
            // Dynamically build INSERT based on which columns exist in the transactions table.
            $colsStmt = $this->conn->query("SHOW COLUMNS FROM transactions");
            $existingCols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
            $available = array_flip($existingCols);

            $mapping = [
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
                'txn_id' => $txn_id,
                'provider' => $provider,
                'customer_ref' => $customer_ref,
                'method' => $method,
                'provider_response' => $provider_response,
                'status' => $status
            ];

            $insertCols = [];
            $placeholders = [];
            $params = [];
            foreach ($mapping as $col => $val) {
                if (isset($available[$col])) {
                    $insertCols[] = $col;
                    $placeholders[] = ':' . $col;
                    $params[$col] = $val;
                }
            }

            if (empty($insertCols)) {
                throw new Exception('No transaction columns available to insert.');
            }

            $query = "INSERT INTO transactions (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->conn->prepare($query);
            foreach ($params as $col => $val) {
                $stmt->bindValue(':' . $col, $val);
            }
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Transaction recording failed: " . $e->getMessage());
        }
    }

    public function getTransactionHistory($user_id, $limit = 20) {
        try {
            $query = "SELECT t.*, u_sender.name as sender_name, u_receiver.name as receiver_name
                     FROM transactions t
                     LEFT JOIN users u_sender ON t.sender_id = u_sender.id
                     LEFT JOIN users u_receiver ON t.receiver_id = u_receiver.id
                     WHERE t.sender_id = :user_id OR t.receiver_id = :user_id
                     ORDER BY t.created_at DESC
                     LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Transaction history retrieval failed: " . $e->getMessage());
        }
    }

    public function updateProfile($id, $data) {
        try {
            $query = "UPDATE " . $this->table_name . " SET email = :email, address = :address, date_of_birth = :date_of_birth, gender = :gender WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':date_of_birth', $data['date_of_birth']);
            $stmt->bindParam(':gender', $data['gender']);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Profile update failed: " . $e->getMessage());
        }
    }

    public function setPIN($id, $pin) {
        try {
            $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
            $query = "UPDATE " . $this->table_name . " SET pin = :pin WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':pin', $hashed_pin);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("PIN set failed: " . $e->getMessage());
        }
    }

    public function verifyPIN($id, $pin) {
        try {
            $query = "SELECT pin FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return password_verify($pin, $result['pin']);
            }
            return false;
        } catch (PDOException $e) {
            throw new Exception("PIN verification failed: " . $e->getMessage());
        }
    }

    public function updateKYCStatus($id, $status, $documents = null) {
        try {
            $query = "UPDATE " . $this->table_name . " SET kyc_status = :status, kyc_documents = :documents WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':documents', $documents);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("KYC update failed: " . $e->getMessage());
        }
    }

    public function updateLoginInfo($id, $deviceToken = null) {
        try {
            $query = "UPDATE " . $this->table_name . " SET last_login = NOW(), failed_login_attempts = 0, device_token = :device_token WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':device_token', $deviceToken);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Login info update failed: " . $e->getMessage());
        }
    }

    public function incrementFailedLogin($phone) {
        try {
            $query = "UPDATE " . $this->table_name . " SET failed_login_attempts = failed_login_attempts + 1 WHERE phone = :phone";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':phone', $phone);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Failed login increment failed: " . $e->getMessage());
        }
    }

    public function isAccountLocked($phone) {
        try {
            $query = "SELECT account_locked_until FROM " . $this->table_name . " WHERE phone = :phone";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':phone', $phone);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result['account_locked_until'] && strtotime($result['account_locked_until']) > time()) {
                    return true;
                }
            }
            return false;
        } catch (PDOException $e) {
            throw new Exception("Account lock check failed: " . $e->getMessage());
        }
    }

    public function lockAccount($phone, $minutes = 30) {
        try {
            $lockTime = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
            $query = "UPDATE " . $this->table_name . " SET account_locked_until = :lock_time WHERE phone = :phone";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':lock_time', $lockTime);
            $stmt->bindParam(':phone', $phone);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Account lock failed: " . $e->getMessage());
        }
    }

    public function resetFailedLoginAttempts($phone) {
        try {
            $query = "UPDATE " . $this->table_name . " SET failed_login_attempts = 0, account_locked_until = NULL WHERE phone = :phone";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':phone', $phone);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Failed login reset failed: " . $e->getMessage());
        }
    }
    public function updateKycDocuments($id, $kyc_documents) {
        try {
            $query = "UPDATE " . $this->table_name . " SET kyc_documents = :kyc_documents WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':kyc_documents', $kyc_documents);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("KYC documents update failed: " . $e->getMessage());
        }
    }

    public function updateProfilePicture($id, $profile_picture_path) {
        try {
            $query = "UPDATE " . $this->table_name . " SET profile_picture = :profile_picture WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':profile_picture', $profile_picture_path);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Profile picture update failed: " . $e->getMessage());
        }
    }
}
?>
