<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'posts' => [], 'users' => []]);
    exit;
}

$search_term = '%' . $query . '%';

try {
    // Search posts
    $posts_stmt = $conn->prepare("
        SELECT 
            p.id,
            p.content,
            p.image_url,
            p.video_url,
            p.created_at,
            u.id as user_id,
            u.username,
            u.display_name,
            u.avatar_url,
            COUNT(DISTINCT r.id) as reaction_count,
            COUNT(DISTINCT c.id) as comment_count,
            MAX(CASE WHEN r.user_id = ? THEN 1 ELSE 0 END) as user_reacted
        FROM posts p
        INNER JOIN users u ON p.user_id = u.id
        LEFT JOIN reactions r ON p.id = r.post_id
        LEFT JOIN comments c ON p.id = c.post_id AND c.is_deleted = 0
        WHERE p.is_deleted = 0 
        AND (p.content LIKE ? OR u.display_name LIKE ? OR u.username LIKE ?)
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $posts_stmt->execute([getCurrentUserId(), $search_term, $search_term, $search_term]);
    $posts = $posts_stmt->fetchAll();
    
    // Search users
    $users_stmt = $conn->prepare("
        SELECT id, username, display_name, avatar_url
        FROM users
        WHERE is_active = 1
        AND (username LIKE ? OR display_name LIKE ?)
        AND id != ?
        LIMIT 10
    ");
    $users_stmt->execute([$search_term, $search_term, getCurrentUserId()]);
    $users = $users_stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'posts' => $posts ?: [],
        'users' => $users ?: []
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Search failed']);
}
?>
