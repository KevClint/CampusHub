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
$reason = $_POST['reason'] ?? '';
$user_id = getCurrentUserId();

$valid_reasons = ['spam', 'harassment', 'inappropriate', 'misinformation', 'other'];

if (!$post_id || !in_array($reason, $valid_reasons)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

try {
    // Check if user already reported this post
    $check = $conn->prepare("SELECT id FROM reports WHERE post_id = ? AND reported_by = ?");
    $check->execute([$post_id, $user_id]);
    
    if ($check->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Already reported']);
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO reports (reported_by, post_id, reason) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $post_id, $reason]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit report']);
}
?>
