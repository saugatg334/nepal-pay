<?php
require_once __DIR__ . '/../config/database.php';

class WalletComparison {
    private $conn;
    private $table_name = "wallet_comparisons";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function saveComparison($data) {
        $query = "INSERT INTO " . $this->table_name . "
                  (user_id, wallet_name, ui_rating, ux_rating, security_rating,
                   ease_of_use_rating, features_rating, overall_rating,
                   strengths, weaknesses, suggestions, preferred_wallet)
                  VALUES
                  (:user_id, :wallet_name, :ui_rating, :ux_rating, :security_rating,
                   :ease_of_use_rating, :features_rating, :overall_rating,
                   :strengths, :weaknesses, :suggestions, :preferred_wallet)";

        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $data = array_map('htmlspecialchars', $data);

        $stmt->bindParam(":user_id", $data['user_id']);
        $stmt->bindParam(":wallet_name", $data['wallet_name']);
        $stmt->bindParam(":ui_rating", $data['ui_rating']);
        $stmt->bindParam(":ux_rating", $data['ux_rating']);
        $stmt->bindParam(":security_rating", $data['security_rating']);
        $stmt->bindParam(":ease_of_use_rating", $data['ease_of_use_rating']);
        $stmt->bindParam(":features_rating", $data['features_rating']);
        $stmt->bindParam(":overall_rating", $data['overall_rating']);
        $stmt->bindParam(":strengths", $data['strengths']);
        $stmt->bindParam(":weaknesses", $data['weaknesses']);
        $stmt->bindParam(":suggestions", $data['suggestions']);
        $stmt->bindParam(":preferred_wallet", $data['preferred_wallet']);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getUserComparisons($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllComparisons() {
        $query = "SELECT wc.*, u.name as user_name FROM " . $this->table_name . " wc
                  JOIN users u ON wc.user_id = u.id
                  ORDER BY wc.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWalletStats($wallet_name) {
        $query = "SELECT
                  COUNT(*) as total_reviews,
                  AVG(ui_rating) as avg_ui,
                  AVG(ux_rating) as avg_ux,
                  AVG(security_rating) as avg_security,
                  AVG(ease_of_use_rating) as avg_ease_of_use,
                  AVG(features_rating) as avg_features,
                  AVG(overall_rating) as avg_overall
                  FROM " . $this->table_name . " WHERE wallet_name = :wallet_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":wallet_name", $wallet_name);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
