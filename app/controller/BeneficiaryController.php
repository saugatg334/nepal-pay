<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Beneficiary.php';
require_once __DIR__ . '/../models/User.php';

class BeneficiaryController extends Controller {
    public function __construct() {
        $this->requireLogin();
    }

    public function index() {
        $userId = Session::get('user_id');
        $beneficiaries = Beneficiary::getWithBanks($userId);
        
        $this->render('beneficiary/index', ['beneficiaries' => $beneficiaries]);
    }

    public function toggleFavorite() {
        $this->validateCSRF();
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'Invalid ID']);
        }
        
        $userId = Session::get('user_id');
        Beneficiary::toggleFavorite($id);
        
        jsonResponse(['success' => true, 'message' => 'Favorite toggled']);
    }

    public function transfer() {
        $this->validateCSRF();
        
        $beneficiaryId = intval($_POST['beneficiary_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        
        if ($amount <= 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid amount']);
        }
        
        $userId = Session::get('user_id');
        
        // TODO: Implement full transfer logic using Wallet/Transaction models
        // For now, redirect to send_money with prefilled data
        $beneficiary = Beneficiary::find($beneficiaryId);
        if (!$beneficiary || $beneficiary['user_id'] != $userId) {
            jsonResponse(['success' => false, 'message' => 'Invalid beneficiary']);
        }
        
        $targetUser = User::find($beneficiary['beneficiary_user_id']);
        jsonResponse([
            'success' => true, 
            'redirect' => APP_URL . '/index.php?page=send_money&receiver=' . $targetUser['phone'] . '&amount=' . $amount
        ]);
    }

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAdd();
        }
        
        $this->render('beneficiary/add');
    }

    private function handleAdd() {
        $this->validateCSRF();
        
        $identifier = $_POST['identifier'] ?? '';
        $nickname = $_POST['nickname'] ?? '';
        
        if (empty($identifier)) {
            flash('error', 'Please enter phone number or email');
            $this->back();
        }
        
        $userId = Session::get('user_id');
        
        $beneficiary = User::findByPhoneOrEmail($identifier);
        
        if (!$beneficiary) {
            flash('error', 'User not found');
            $this->back();
        }
        
        if ($beneficiary['id'] == $userId) {
            flash('error', 'Cannot add yourself as beneficiary');
            $this->back();
        }
        
        if (Beneficiary::isBeneficiary($userId, $beneficiary['id'])) {
            flash('error', 'User already in beneficiaries');
            $this->back();
        }
        
        Beneficiary::add($userId, $beneficiary['id'], $nickname);
        
        flash('success', $beneficiary['full_name'] . ' added as beneficiary');
        $this->redirect(APP_URL . '/index.php?page=beneficiaries');
    }

    public function remove() {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            flash('error', 'Invalid request');
            $this->back();
        }
        
        $userId = Session::get('user_id');
        
        $sql = "DELETE FROM beneficiaries WHERE id = ? AND user_id = ?";
        Database::query($sql, [$id, $userId]);
        
        flash('success', 'Beneficiary removed');
        $this->redirect(APP_URL . '/index.php?page=beneficiaries');
    }

    public function search() {
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 3) {
            jsonResponse(['success' => false, 'message' => 'Search query too short']);
        }
        
        $userId = Session::get('user_id');
        
        if (isValidPhone($query)) {
            $results = User::searchByPhone($userId, $query);
        } else {
            $results = User::searchByEmail($userId, $query);
        }
        
        jsonResponse(['success' => true, 'results' => $results]);
    }

    public function searchUser() {
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 3) {
            jsonResponse(['success' => false, 'message' => 'Search query too short']);
        }
        
        $user = User::findByPhoneOrEmail($query);
        
        if ($user) {
            jsonResponse(['success' => true, 'user' => [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'phone' => $user['phone']
            ]]);
        }
        
        jsonResponse(['success' => false, 'message' => 'User not found']);
    }

    protected function requireLogin() {
        Session::init();
        
        if (!Session::has('user_id')) {
            $this->unauthorized();
        }
    }
}