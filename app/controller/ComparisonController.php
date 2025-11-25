<?php
require_once __DIR__ . '/../models/WalletComparison.php';
require_once __DIR__ . '/../helpers/session_helper.php';

class ComparisonController {
    private $comparisonModel;

    public function __construct() {
        $this->comparisonModel = new WalletComparison();
    }

    public function submitComparison() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return "Invalid request method.";
        }

        if (!isset($_SESSION['user_id'])) {
            return "Please login to submit comparison.";
        }

        $required_fields = ['wallet_name', 'ui_rating', 'ux_rating', 'security_rating',
                          'ease_of_use_rating', 'features_rating', 'overall_rating'];

        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                return "All rating fields are required.";
            }
        }

        $data = [
            'user_id' => $_SESSION['user_id'],
            'wallet_name' => $_POST['wallet_name'],
            'ui_rating' => (int)$_POST['ui_rating'],
            'ux_rating' => (int)$_POST['ux_rating'],
            'security_rating' => (int)$_POST['security_rating'],
            'ease_of_use_rating' => (int)$_POST['ease_of_use_rating'],
            'features_rating' => (int)$_POST['features_rating'],
            'overall_rating' => (int)$_POST['overall_rating'],
            'strengths' => $_POST['strengths'] ?? '',
            'weaknesses' => $_POST['weaknesses'] ?? '',
            'suggestions' => $_POST['suggestions'] ?? '',
            'preferred_wallet' => $_POST['preferred_wallet'] ?? null
        ];

        // Validate ratings (1-5)
        $rating_fields = ['ui_rating', 'ux_rating', 'security_rating',
                         'ease_of_use_rating', 'features_rating', 'overall_rating'];

        foreach ($rating_fields as $field) {
            if ($data[$field] < 1 || $data[$field] > 5) {
                return "Ratings must be between 1 and 5.";
            }
        }

        if ($this->comparisonModel->saveComparison($data)) {
            setFlash('success', 'Thank you for your feedback! Your comparison has been saved.');
            return true;
        } else {
            return "Failed to save comparison. Please try again.";
        }
    }

    public function getUserComparisons() {
        if (!isset($_SESSION['user_id'])) {
            return [];
        }
        return $this->comparisonModel->getUserComparisons($_SESSION['user_id']);
    }

    public function getAllComparisons() {
        return $this->comparisonModel->getAllComparisons();
    }

    public function getWalletStats($wallet_name) {
        return $this->comparisonModel->getWalletStats($wallet_name);
    }
}
?>
