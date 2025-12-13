<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$userModel = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['error'] = 'Invalid file type. Only JPEG, PNG, and GIF are allowed.';
            header('Location: dashboard.php');
            exit();
        }

        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            $_SESSION['error'] = 'File size must be less than 5MB.';
            header('Location: dashboard.php');
            exit();
        }

        // Create uploads directory if it doesn't exist
        $upload_dir = __DIR__ . '/assets/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Update database
            $relative_path = 'assets/uploads/' . $filename;
            if ($userModel->updateProfilePicture($user_id, $relative_path)) {
                $_SESSION['success'] = 'Profile picture updated successfully.';
            } else {
                $_SESSION['error'] = 'Failed to update profile picture in database.';
            }
        } else {
            $_SESSION['error'] = 'Failed to upload file.';
        }
    } else {
        $_SESSION['error'] = 'No file uploaded or upload error.';
    }

    header('Location: dashboard.php');
    exit();
} else {
    header('Location: dashboard.php');
    exit();
}
?>
