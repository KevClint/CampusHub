<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$conversation_id = (int)($_GET['conversation_id'] ?? $_POST['conversation_id'] ?? 0);
$user_id = getCurrentUserId();

// Handle GET - fetch messages
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$conversation_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID is required']);
        exit();
    }
    
    // Verify user is participant
    $check = $conn->prepare("SELECT id FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
    $check->execute([$conversation_id, $user_id]);
    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit();
    }
    
    $stmt = $conn->prepare("
        SELECT m.id, m.conversation_id, m.sender_id, m.content, m.created_at,
               u.id as user_id, u.username, u.display_name, u.avatar_url
        FROM messages m
        INNER JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ? AND m.is_deleted = 0
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll();
    
    // Mark as read
    $update = $conn->prepare("UPDATE conversation_participants SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?");
    $update->execute([$conversation_id, $user_id]);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    exit();
}

// Handle POST - send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    
    if (!$conversation_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID is required. Received: ' . json_encode($_POST)]);
        exit();
    }
    
    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message content is required']);
        exit();
    }
    
    // Verify user is participant
    $check = $conn->prepare("SELECT id FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
    $check->execute([$conversation_id, $user_id]);
    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'You are not a participant in this conversation']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$conversation_id, $user_id, $content]);
        
        // Update conversation timestamp
        $update = $conn->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
        $update->execute([$conversation_id]);
        
        $message_id = $conn->lastInsertId();
        
        // Get the created message
        $get_message = $conn->prepare("
            SELECT m.id, m.conversation_id, m.sender_id, m.content, m.created_at, 
                   u.id as user_id, u.username, u.display_name, u.avatar_url
            FROM messages m
            INNER JOIN users u ON m.sender_id = u.id
            WHERE m.id = ?
        ");
        $get_message->execute([$message_id]);
        $message = $get_message->fetch();
        
        if (!$message) {
            http_response_code(500);
            echo json_encode(['error' => 'Message was created but could not be retrieved']);
            exit();
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message: ' . $e->getMessage()]);
    }
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
