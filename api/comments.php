<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$post_id = (int)($_GET['post_id'] ?? $_POST['post_id'] ?? 0);

if (!$post_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Post ID is required']);
    exit();
}

// Handle GET - fetch comments
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.display_name, u.avatar_url
        FROM comments c
        INNER JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ? AND c.is_deleted = 0
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'comments' => $comments
    ]);
    exit();
}

// Handle POST - create comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    $user_id = getCurrentUserId();
    
    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Content is required']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $content]);
        
        $comment_id = $conn->lastInsertId();
        
        // Get the created comment
        $get_comment = $conn->prepare("
            SELECT c.*, u.username, u.display_name, u.avatar_url
            FROM comments c
            INNER JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ");
        $get_comment->execute([$comment_id]);
        $comment = $get_comment->fetch();
        
        echo json_encode([
            'success' => true,
            'comment' => $comment
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create comment']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
