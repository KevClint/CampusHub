<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$user_id = getCurrentUserId();

// Get conversation ID from GET or POST
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : (isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0);

if (!$conversation_id) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Conversation ID required']));
}

// Verify user is participant
$verify = $conn->prepare("SELECT id FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
$verify->execute([$conversation_id, $user_id]);
if (!$verify->fetch()) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Access denied']));
}

// Handle GET - fetch messages
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messages = $conn->prepare("
        SELECT 
            m.id,
            m.content,
            m.created_at,
            m.sender_id,
            u.username,
            u.display_name,
            u.avatar_url
        FROM messages m
        INNER JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ? AND m.is_deleted = 0
        ORDER BY m.created_at ASC
    ");
    $messages->execute([$conversation_id]);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages->fetchAll() ?: []
    ]);
    exit;
}

// Handle POST - send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    if (empty($content)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Message cannot be empty']));
    }
    
    try {
        // Insert message
        $insert = $conn->prepare("
            INSERT INTO messages (conversation_id, sender_id, content, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $insert->execute([$conversation_id, $user_id, $content]);
        $message_id = $conn->lastInsertId();
        
        // Update conversation timestamp
        $update_conv = $conn->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
        $update_conv->execute([$conversation_id]);
        
        // Get the message with user info
        $get = $conn->prepare("
            SELECT 
                m.id,
                m.content,
                m.created_at,
                m.sender_id,
                u.username,
                u.display_name,
                u.avatar_url
            FROM messages m
            INNER JOIN users u ON m.sender_id = u.id
            WHERE m.id = ?
        ");
        $get->execute([$message_id]);
        $message = $get->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to send message']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
?>
