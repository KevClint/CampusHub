<?php
/**
 * Message Status Handler
 * Updates and retrieves message delivery/seen status
 * Supports:
 * - Mark messages as delivered when fetched by receiver
 * - Mark messages as seen when conversation is opened
 * - Get status of specific messages
 */

require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$user_id = getCurrentUserId();
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : null);

if (!$action) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Action required']));
}

switch ($action) {
    case 'mark_delivered':
        /**
         * Mark all messages in a conversation as delivered
         * Called when receiver fetches messages
         */
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
        
        // Mark messages from other users as delivered (but not seen yet)
        $update = $conn->prepare("
            UPDATE messages 
            SET status = 'delivered'
            WHERE conversation_id = ? 
            AND sender_id != ?
            AND status = 'sent'
        ");
        $result = $update->execute([$conversation_id, $user_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Messages marked as delivered']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to update message status']);
        }
        break;
    
    case 'mark_seen':
        /**
         * Mark all messages in a conversation as seen
         * Called when user reads messages
         */
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
        
        // Mark messages from other users as seen
        $update = $conn->prepare("
            UPDATE messages 
            SET status = 'seen', seen_at = NOW()
            WHERE conversation_id = ? 
            AND sender_id != ?
            AND status IN ('sent', 'delivered')
        ");
        $result = $update->execute([$conversation_id, $user_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Messages marked as seen']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to update message status']);
        }
        break;
    
    case 'get_status':
        /**
         * Get status of all messages in a conversation
         * Returns mapping of message_id -> status
         */
        $conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
        
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
        
        // Get all message statuses - only show statuses for messages sent BY this user
        $query = $conn->prepare("
            SELECT id, status, seen_at
            FROM messages
            WHERE conversation_id = ?
            AND sender_id = ?
            ORDER BY created_at DESC
            LIMIT 100
        ");
        $query->execute([$conversation_id, $user_id]);
        $messages = $query->fetchAll();
        
        // Create map of message_id -> status info
        $statusMap = [];
        foreach ($messages as $msg) {
            $statusMap[$msg['id']] = [
                'status' => $msg['status'],
                'seen_at' => $msg['seen_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'statuses' => $statusMap
        ]);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
