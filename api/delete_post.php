<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$post_id = (int)($_POST['post_id'] ?? 0);
$user_id = getCurrentUserId();

if (!$post_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Post ID is required']);
    exit();
}

try {
    // Verify post belongs to user
    $check = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $check->execute([$post_id]);
    $post = $check->fetch();
    
    if (!$post || $post['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit();
    }
    
    // Soft delete
    $stmt = $conn->prepare("UPDATE posts SET is_deleted = 1 WHERE id = ?");
    $stmt->execute([$post_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete post']);
}
?>
