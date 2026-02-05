<?php
/**
 * Avatar Upload Handler
 * Handles profile picture uploads for users
 */

require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$user_id = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'No file uploaded or upload error']));
}

try {
    $file = $_FILES['avatar'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP allowed.');
    }
    
    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        throw new Exception('File size exceeds 5MB limit');
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = '../uploads/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save file');
    }
    
    // Relative path for database storage
    $avatar_url = 'uploads/avatars/' . $filename;
    
    // Update user avatar in database
    $update = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
    $update->execute([$avatar_url, $user_id]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Avatar updated successfully',
        'avatar_url' => $avatar_url
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
