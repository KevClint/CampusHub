<?php
/**
 * Mark messages as read / Update notification status
 * Handles:
 * - Marking all messages in a conversation as read
 * - Fetching total unread message count for header badge
 */
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$user_id = getCurrentUserId();
$action = isset($_GET['action']) ?: (isset($_POST['action']) ? $_POST['action'] : 'mark_read');

switch ($action) {
    case 'mark_read':
        // Mark all messages in a conversation as read
        $conversation_id = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
        
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
        
        // Update last_read_at timestamp
        $update = $conn->prepare("
            UPDATE conversation_participants 
            SET last_read_at = NOW() 
            WHERE conversation_id = ? AND user_id = ?
        ");
        $update->execute([$conversation_id, $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Messages marked as read']);
        break;
    
    case 'get_unread_count':
        // Get total unread message count for current user across all conversations
        $query = $conn->prepare("
            SELECT COUNT(DISTINCT m.id) as unread_count
            FROM messages m
            INNER JOIN conversations c ON m.conversation_id = c.id
            INNER JOIN conversation_participants cp ON c.id = cp.conversation_id
            WHERE cp.user_id = ?
            AND m.created_at > COALESCE(cp.last_read_at, '2000-01-01')
            AND m.sender_id != ?
            AND m.is_deleted = 0
        ");
        $query->execute([$user_id, $user_id]);
        $result = $query->fetch();
        
        echo json_encode([
            'success' => true,
            'unread_count' => (int)$result['unread_count']
        ]);
        break;
    
    case 'get_conversation_unread':
        // Get unread count for a specific conversation
        $conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
        
        if (!$conversation_id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Conversation ID required']));
        }
        
        $query = $conn->prepare("
            SELECT COUNT(*) as unread_count
            FROM messages m
            WHERE m.conversation_id = ?
            AND m.created_at > COALESCE(
                (SELECT last_read_at FROM conversation_participants WHERE conversation_id = ? AND user_id = ?),
                '2000-01-01'
            )
            AND m.sender_id != ?
            AND m.is_deleted = 0
        ");
        $query->execute([$conversation_id, $conversation_id, $user_id, $user_id]);
        $result = $query->fetch();
        
        echo json_encode([
            'success' => true,
            'unread_count' => (int)$result['unread_count']
        ]);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
