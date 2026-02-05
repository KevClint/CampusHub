<?php
/**
 * Typing Indicator Handler
 * Manages real-time typing status for conversations
 * Uses database table for persistence (survives across polls)
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

try {
    switch ($action) {
        case 'start_typing':
            /**
             * User started typing
             * Insert or update typing indicator record
             */
            $conversation_id = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
            
            if (!$conversation_id) {
                throw new Exception('Conversation ID required');
            }
            
            // Verify user is participant
            $verify = $conn->prepare("SELECT id FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
            $verify->execute([$conversation_id, $user_id]);
            if (!$verify->fetch()) {
                throw new Exception('Access denied', 403);
            }
            
            // Insert or update typing indicator
            $stmt = $conn->prepare("
                INSERT INTO typing_indicators (conversation_id, user_id, typing, created_at, updated_at)
                VALUES (?, ?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    typing = 1,
                    updated_at = NOW()
            ");
            $stmt->execute([$conversation_id, $user_id]);
            
            echo json_encode(['success' => true]);
            break;
        
        case 'stop_typing':
            /**
             * User stopped typing
             * Remove or mark typing indicator as inactive
             */
            $conversation_id = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
            
            if (!$conversation_id) {
                throw new Exception('Conversation ID required');
            }
            
            // Delete typing indicator
            $delete = $conn->prepare("DELETE FROM typing_indicators WHERE conversation_id = ? AND user_id = ?");
            $delete->execute([$conversation_id, $user_id]);
            
            echo json_encode(['success' => true]);
            break;
        
        case 'get_typing':
            /**
             * Get who is typing in a conversation
             * Excludes stale records (older than 5 seconds)
             */
            $conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
            
            if (!$conversation_id) {
                throw new Exception('Conversation ID required');
            }
            
            // Verify user is participant
            $verify = $conn->prepare("SELECT id FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
            $verify->execute([$conversation_id, $user_id]);
            if (!$verify->fetch()) {
                throw new Exception('Access denied', 403);
            }
            
            // Get typing users (exclude self, exclude stale records over 5 seconds old)
            $query = $conn->prepare("
                SELECT u.id, u.display_name, u.avatar_url
                FROM typing_indicators ti
                INNER JOIN users u ON ti.user_id = u.id
                WHERE ti.conversation_id = ?
                AND ti.user_id != ?
                AND ti.typing = 1
                AND ti.updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)
                ORDER BY ti.updated_at DESC
            ");
            $query->execute([$conversation_id, $user_id]);
            $typing_users = $query->fetchAll();
            
            echo json_encode([
                'success' => true,
                'typing_users' => $typing_users ?: []
            ]);
            break;
        
        case 'cleanup':
            /**
             * Clean up stale typing indicators (older than 10 seconds)
             * Can be called periodically to maintain database health
             */
            $cleanup = $conn->prepare("
                DELETE FROM typing_indicators 
                WHERE updated_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)
            ");
            $cleanup->execute();
            
            echo json_encode(['success' => true]);
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    // Ensure HTTP code is a valid integer
    $code = (int)($e->getCode() ?: 400);
    // Validate it's a valid HTTP status code
    if ($code < 100 || $code >= 600) {
        $code = 400;
    }
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
