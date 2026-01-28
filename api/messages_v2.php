<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$user_id = getCurrentUserId();
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
    $action = isset($_GET['action']) ? $_GET['action'] : 'messages';
    
    if ($action === 'pinned') {
        // Get pinned messages
        $messages = $conn->prepare("
            SELECT 
                m.id,
                m.content,
                m.created_at,
                m.sender_id,
                u.username,
                u.display_name,
                u.avatar_url
            FROM pinned_messages pm
            INNER JOIN messages m ON pm.message_id = m.id
            INNER JOIN users u ON m.sender_id = u.id
            WHERE pm.conversation_id = ?
            ORDER BY pm.pinned_at DESC
            LIMIT 3
        ");
        $messages->execute([$conversation_id]);
        
        echo json_encode([
            'success' => true,
            'pinned_messages' => $messages->fetchAll() ?: []
        ]);
    } else {
        // Get regular messages (excluding unsent ones unless unsent for you)
        $messages = $conn->prepare("
            SELECT 
                m.id,
                m.content,
                m.created_at,
                m.sender_id,
                m.file_url,
                m.file_name,
                m.is_unsent,
                m.unsent_for_all,
                u.username,
                u.display_name,
                u.avatar_url
            FROM messages m
            INNER JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ?
            AND m.is_deleted = 0
            AND (m.unsent_for_all = 0 OR m.sender_id = ?)
            ORDER BY m.created_at ASC
        ");
        $messages->execute([$conversation_id, $user_id]);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages->fetchAll() ?: []
        ]);
    }
    exit;
}

// Handle POST - send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : 'send';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $message_id = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
    
    switch ($action) {
        case 'send':
            if (empty($content) && !isset($_FILES['file'])) {
                http_response_code(400);
                die(json_encode(['success' => false, 'error' => 'Message cannot be empty']));
            }
            
            $file_url = null;
            $file_name = null;
            $file_size = null;
            
            // Handle file upload
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'text/plain', 'application/msword', 'application/zip'];
                $file_type = $_FILES['file']['type'];
                
                if (in_array($file_type, $allowed)) {
                    $upload_dir = '../uploads/messages/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '_' . time() . '.' . $extension;
                    $target_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
                        $file_url = 'uploads/messages/' . $filename;
                        $file_name = $_FILES['file']['name'];
                        $file_size = $_FILES['file']['size'];
                    }
                }
            }
            
            try {
                $insert = $conn->prepare("
                    INSERT INTO messages (conversation_id, sender_id, content, file_url, file_name, file_size, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $insert->execute([$conversation_id, $user_id, $content, $file_url, $file_name, $file_size]);
                $message_id = $conn->lastInsertId();
                
                $update_conv = $conn->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
                $update_conv->execute([$conversation_id]);
                
                $get = $conn->prepare("
                    SELECT 
                        m.id,
                        m.content,
                        m.created_at,
                        m.sender_id,
                        m.file_url,
                        m.file_name,
                        m.is_unsent,
                        u.username,
                        u.display_name,
                        u.avatar_url
                    FROM messages m
                    INNER JOIN users u ON m.sender_id = u.id
                    WHERE m.id = ?
                ");
                $get->execute([$message_id]);
                $message = $get->fetch();
                
                echo json_encode(['success' => true, 'message' => $message]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to send message']);
            }
            break;
        
        case 'unsend':
            $unsend_for_all = isset($_POST['unsend_for_all']) && $_POST['unsend_for_all'] === 'true' ? 1 : 0;
            
            // Verify user is message owner
            $msg = $conn->prepare("SELECT sender_id FROM messages WHERE id = ?");
            $msg->execute([$message_id]);
            $message = $msg->fetch();
            
            if (!$message || $message['sender_id'] != $user_id) {
                http_response_code(403);
                die(json_encode(['success' => false, 'error' => 'Cannot unsend this message']));
            }
            
            $update = $conn->prepare("
                UPDATE messages 
                SET is_unsent = 1, unsent_for_all = ? 
                WHERE id = ?
            ");
            $update->execute([$unsend_for_all, $message_id]);
            
            echo json_encode(['success' => true, 'message' => 'Message unsent']);
            break;
        
        case 'edit':
            $new_content = isset($_POST['new_content']) ? trim($_POST['new_content']) : '';
            
            if (empty($new_content)) {
                http_response_code(400);
                die(json_encode(['success' => false, 'error' => 'Content cannot be empty']));
            }
            
            // Verify user is message owner
            $msg = $conn->prepare("SELECT sender_id, content FROM messages WHERE id = ?");
            $msg->execute([$message_id]);
            $message = $msg->fetch();
            
            if (!$message || $message['sender_id'] != $user_id) {
                http_response_code(403);
                die(json_encode(['success' => false, 'error' => 'Cannot edit this message']));
            }
            
            // Save edit history
            $history = $conn->prepare("INSERT INTO message_edits (message_id, old_content) VALUES (?, ?)");
            $history->execute([$message_id, $message['content']]);
            
            // Update message
            $update = $conn->prepare("UPDATE messages SET content = ? WHERE id = ?");
            $update->execute([$new_content, $message_id]);
            
            echo json_encode(['success' => true, 'message' => 'Message edited']);
            break;
        
        case 'pin':
            // Check if already pinned
            $check = $conn->prepare("SELECT id FROM pinned_messages WHERE message_id = ? AND conversation_id = ?");
            $check->execute([$message_id, $conversation_id]);
            
            if ($check->fetch()) {
                // Unpin
                $delete = $conn->prepare("DELETE FROM pinned_messages WHERE message_id = ? AND conversation_id = ?");
                $delete->execute([$message_id, $conversation_id]);
                echo json_encode(['success' => true, 'pinned' => false]);
            } else {
                // Check if already 3 pinned
                $count = $conn->prepare("SELECT COUNT(*) as cnt FROM pinned_messages WHERE conversation_id = ?");
                $count->execute([$conversation_id]);
                $result = $count->fetch();
                
                if ($result['cnt'] >= 3) {
                    http_response_code(400);
                    die(json_encode(['success' => false, 'error' => 'Maximum 3 pinned messages allowed']));
                }
                
                // Pin the message
                $pin = $conn->prepare("
                    INSERT INTO pinned_messages (message_id, conversation_id, pinned_by)
                    VALUES (?, ?, ?)
                ");
                $pin->execute([$message_id, $conversation_id, $user_id]);
                echo json_encode(['success' => true, 'pinned' => true]);
            }
            break;
        
        case 'delete':
            // Verify user is message owner
            $msg = $conn->prepare("SELECT sender_id FROM messages WHERE id = ?");
            $msg->execute([$message_id]);
            $message = $msg->fetch();
            
            if (!$message || $message['sender_id'] != $user_id) {
                http_response_code(403);
                die(json_encode(['success' => false, 'error' => 'Cannot delete this message']));
            }
            
            $delete = $conn->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ?");
            $delete->execute([$message_id]);
            
            echo json_encode(['success' => true, 'message' => 'Message deleted']);
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
?>
