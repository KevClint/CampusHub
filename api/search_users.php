<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

if (empty($query)) {
    echo json_encode(['users' => []]);
    exit();
}

$user_id = getCurrentUserId();

$stmt = $conn->prepare("
    SELECT id, username, display_name, avatar_url, year_level
    FROM users
    WHERE (username LIKE ? OR display_name LIKE ?)
    AND id != ?
    AND is_active = 1
    LIMIT 20
");

$search_term = '%' . $query . '%';
$stmt->execute([$search_term, $search_term, $user_id]);
$users = $stmt->fetchAll();

echo json_encode(['users' => $users]);
?>
