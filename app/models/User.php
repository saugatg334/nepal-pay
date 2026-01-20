(UPDATE existing file: append the following three methods inside the User class, after getTransactionHistory and before updateProfile)

    // Find a transaction by its txn_id
    public function findTransactionByTxnId($txn_id) {
        try {
            $query = "SELECT t.*, u.name as sender_name, ur.name as receiver_name, ur.phone as receiver_phone
                      FROM transactions t
                      LEFT JOIN users u ON t.sender_id = u.id
                      LEFT JOIN users ur ON t.receiver_id = ur.id
                      WHERE t.txn_id = :txn_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':txn_id', $txn_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Transaction lookup failed: " . $e->getMessage());
        }
    }

    // Find transactions by type, optionally filtered by status
    public function findTransactionsByType($type, $status = null, $limit = 100) {
        try {
            $sql = "SELECT t.*, u.name as sender_name, ur.name as receiver_name, ur.phone as receiver_phone
                    FROM transactions t
                    LEFT JOIN users u ON t.sender_id = u.id
                    LEFT JOIN users ur ON t.receiver_id = ur.id
                    WHERE t.type = :type";
            if ($status !== null) {
                $sql .= " AND t.status = :status";
            }
            $sql .= " ORDER BY t.created_at DESC LIMIT :limit";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':type', $type);
            if ($status !== null) $stmt->bindParam(':status', $status);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Transaction query failed: " . $e->getMessage());
        }
    }

    // Update transaction status and optional provider_response (or admin note)
    public function updateTransactionStatus($transaction_id, $status, $note = null) {
        try {
            $query = "UPDATE transactions SET status = :status";
            if ($note !== null) {
                $query .= ", provider_response = :note";
            }
            $query .= " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            if ($note !== null) $stmt->bindParam(':note', $note);
            $stmt->bindParam(':id', $transaction_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Update transaction status failed: " . $e->getMessage());
        }
    }