<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$content = trim($_POST['content'] ?? '');
$user_id = getCurrentUserId();

$image_url = null;
$video_url = null;

// Handle image upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $_FILES['image']['type'];
    
    if (in_array($file_type, $allowed)) {
        $upload_dir = '../uploads/posts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_url = 'uploads/posts/' . $filename;
        }
    }
}

// Handle video upload
if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['video/mp4', 'video/webm', 'video/ogg'];
    $file_type = $_FILES['video']['type'];
    
    if (in_array($file_type, $allowed)) {
        $upload_dir = '../uploads/posts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['video']['tmp_name'], $target_path)) {
            $video_url = 'uploads/posts/' . $filename;
        }
    }
}

// Require either content or media (image/video)
if (empty($content) && empty($image_url) && empty($video_url)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please add content, an image, or a video']);
    exit();
}

try {
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image_url, video_url) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $content, $image_url, $video_url]);
    
    $post_id = $conn->lastInsertId();
    
    // Get the created post
    $get_post = $conn->prepare("
        SELECT p.*, u.username, u.display_name, u.avatar_url
        FROM posts p
        INNER JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $get_post->execute([$post_id]);
    $post = $get_post->fetch();
    
    echo json_encode([
        'success' => true,
        'post' => $post
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create post']);
}
?>
