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
    // Check if user already reacted
    $check = $conn->prepare("SELECT id FROM reactions WHERE post_id = ? AND user_id = ?");
    $check->execute([$post_id, $user_id]);
    
    if ($check->fetch()) {
        // Remove reaction
        $stmt = $conn->prepare("DELETE FROM reactions WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        $action = 'removed';
    } else {
        // Add reaction
        $stmt = $conn->prepare("INSERT INTO reactions (post_id, user_id, reaction_type) VALUES (?, ?, 'like')");
        $stmt->execute([$post_id, $user_id]);
        $action = 'added';
    }
    
    // Get updated count
    $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM reactions WHERE post_id = ?");
    $count_stmt->execute([$post_id]);
    $count = $count_stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'count' => $count
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to toggle reaction']);
}
?>
